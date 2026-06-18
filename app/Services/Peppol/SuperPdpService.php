<?php

namespace App\Services\Peppol;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Super PDP — Plateforme Agréée (France) + Access Point Peppol.
 *
 * Super PDP est immatriculée par la DGFiP (facturation électronique française)
 * et certifiée Peppol. API REST / OpenAPI. https://www.superpdp.tech
 *
 * ⚠️ Les éléments marqués "TODO (API Super PDP)" — chemins d'endpoint, champs du
 *    payload et de la réponse, statuts — doivent être confirmés contre la
 *    documentation OpenAPI / le compte sandbox AVANT la mise en production.
 *    Le reste de l'application ne dépend que de PeppolAccessPointInterface, donc
 *    aucune autre modification n'est nécessaire pour activer ce fournisseur.
 */
class SuperPdpService implements PeppolAccessPointInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected bool $sandbox;

    public function __construct()
    {
        $this->apiKey = (string) config('peppol.superpdp.api_key', '');
        $this->apiUrl = rtrim((string) config('peppol.superpdp.api_url', 'https://api.superpdp.tech'), '/');
        $this->sandbox = (bool) config('peppol.superpdp.sandbox', true);
    }

    /**
     * Send an invoice via Super PDP.
     */
    public function sendInvoice(Invoice $invoice, string $peppolXml): PeppolTransmissionResult
    {
        if (!$this->isConfigured()) {
            return PeppolTransmissionResult::failure('Super PDP n\'est pas configuré correctement.');
        }

        try {
            $buyer = $invoice->buyer;
            $recipientId = $buyer['peppol_endpoint_id'] ?? null;
            $recipientScheme = $buyer['peppol_endpoint_scheme'] ?? null;

            if (!$recipientId || !$recipientScheme) {
                return PeppolTransmissionResult::failure('Le destinataire n\'a pas d\'identifiant Peppol configuré.');
            }

            // TODO (API Super PDP) : adapter la structure du payload au contrat réel
            // (OpenAPI / sandbox). Hypothèse : XML Peppol BIS 3.0 (UBL) en base64
            // + routage par identifiant destinataire (scheme 0009=FR:SIRET, 9938=LU:VAT…).
            $payload = [
                'document' => [
                    'format' => 'ubl',
                    'type' => $invoice->isCreditNote() ? 'creditnote' : 'invoice',
                    'content' => base64_encode($peppolXml),
                ],
                'recipient' => [
                    'scheme' => $recipientScheme,
                    'identifier' => $recipientId,
                ],
            ];

            Log::info('Sending invoice to Super PDP', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'recipient_id' => $recipientId,
                'sandbox' => $this->sandbox,
            ]);

            // TODO (API Super PDP) : confirmer le chemin de l'endpoint d'émission.
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/v1/invoices', $payload);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                // TODO (API Super PDP) : confirmer le champ d'identifiant de document.
                $documentId = (string) ($data['id'] ?? $data['documentId'] ?? $data['guid'] ?? '');

                Log::info('Invoice sent successfully via Super PDP', [
                    'invoice_id' => $invoice->id,
                    'document_id' => $documentId,
                ]);

                return PeppolTransmissionResult::success($documentId, [
                    'superpdp_response' => $data,
                    'sandbox' => $this->sandbox,
                ]);
            }

            $errorData = $response->json() ?? [];
            $errorMessage = $this->parseError($errorData) ?: ('Erreur HTTP ' . $response->status());

            Log::error('Super PDP API error', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            return PeppolTransmissionResult::failure($errorMessage, [
                'superpdp_error' => $errorData,
                'http_status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error('Super PDP exception', [
                'invoice_id' => $invoice->id,
                'exception' => $e->getMessage(),
            ]);

            return PeppolTransmissionResult::failure('Erreur de connexion à Super PDP: ' . $e->getMessage());
        }
    }

    /**
     * Get the status of a transmission.
     */
    public function getTransmissionStatus(string $documentId): string
    {
        if (!$this->isConfigured()) {
            return 'unknown';
        }

        try {
            // TODO (API Super PDP) : confirmer le chemin de consultation de statut.
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/v1/invoices/' . $documentId);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                return $this->mapStatus((string) ($data['status'] ?? 'unknown'));
            }

            return 'unknown';
        } catch (\Exception $e) {
            Log::error('Super PDP status check failed', [
                'document_id' => $documentId,
                'exception' => $e->getMessage(),
            ]);

            return 'unknown';
        }
    }

    /**
     * Check if Super PDP is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return $this->sandbox ? 'Super PDP (Sandbox)' : 'Super PDP';
    }

    /**
     * Map a Super PDP status to our internal status (sent, delivered, failed).
     * TODO (API Super PDP) : aligner sur les statuts réels renvoyés par l'API.
     */
    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'queued', 'received', 'processing' => 'sent',
            'sent', 'delivered', 'accepted', 'completed' => 'delivered',
            'failed', 'rejected', 'error' => 'failed',
            default => 'sent',
        };
    }

    /**
     * Parse a Super PDP error response into a readable message.
     */
    protected function parseError(array $errorData): string
    {
        if (isset($errorData['message'])) {
            return (string) $errorData['message'];
        }

        if (isset($errorData['error'])) {
            return is_array($errorData['error'])
                ? ($errorData['error']['message'] ?? json_encode($errorData['error']))
                : (string) $errorData['error'];
        }

        if (isset($errorData['errors']) && is_array($errorData['errors'])) {
            $messages = [];
            foreach ($errorData['errors'] as $error) {
                $messages[] = is_array($error) ? ($error['message'] ?? json_encode($error)) : (string) $error;
            }
            return implode('; ', $messages);
        }

        return '';
    }
}
