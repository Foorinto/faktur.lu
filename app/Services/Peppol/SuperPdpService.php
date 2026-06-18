<?php

namespace App\Services\Peppol;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Super PDP — Plateforme Agréée (France) + Access Point Peppol.
 *
 * Immatriculée DGFiP (facturation électronique FR) + certifiée Peppol.
 * API REST documentée : https://www.superpdp.tech/documentation
 *
 * Authentification : OAuth 2.0 client_credentials. Chaque entreprise dispose
 * d'une « application » (client_id + client_secret) créée dans la console Super PDP.
 * Le couple sandbox/production est déterminé par les identifiants de l'application.
 *
 * ⚠️ Limite connue (multi-tenant) : ces identifiants sont aujourd'hui pris dans la
 *    config (un seul émetteur). Pour le SaaS multi-clients, il faudra stocker un
 *    client_id/secret Super PDP par utilisateur faktur.lu (chantier de suivi).
 */
class SuperPdpService implements PeppolAccessPointInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $endpoint;
    protected bool $sandbox;
    protected ?string $token = null;

    public function __construct()
    {
        $this->clientId = (string) config('peppol.superpdp.client_id', '');
        $this->clientSecret = (string) config('peppol.superpdp.client_secret', '');
        $this->endpoint = rtrim((string) config('peppol.superpdp.endpoint', 'https://api.superpdp.tech'), '/');
        $this->sandbox = (bool) config('peppol.superpdp.sandbox', true);
    }

    /**
     * Send an invoice via Super PDP.
     * Le XML Peppol BIS 3.0 (UBL) est envoyé en corps brut ; le destinataire est
     * déterminé par le XML lui-même (pas de routage séparé).
     */
    public function sendInvoice(Invoice $invoice, string $peppolXml): PeppolTransmissionResult
    {
        if (!$this->isConfigured()) {
            return PeppolTransmissionResult::failure('Super PDP n\'est pas configuré correctement.');
        }

        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return PeppolTransmissionResult::failure('Échec de l\'authentification OAuth2 auprès de Super PDP.');
            }

            Log::info('Sending invoice to Super PDP', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'sandbox' => $this->sandbox,
            ]);

            // POST /v1.beta/invoices — corps = XML UBL brut.
            $response = Http::withToken($token)
                ->withBody($peppolXml, 'application/xml')
                ->post($this->endpoint . '/v1.beta/invoices');

            if ($response->successful()) {
                $data = $response->json() ?? [];
                $documentId = (string) ($data['id'] ?? '');

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
            $token = $this->getAccessToken();
            if (!$token) {
                return 'unknown';
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->endpoint . '/v1.beta/invoices/' . $documentId);

            if ($response->successful()) {
                $data = $response->json() ?? [];

                // TODO (API Super PDP) : confirmer le champ de statut exact (lifecycle).
                // Heuristique : présence de 'en_invoice' = facture traitée/transmise.
                if (isset($data['status'])) {
                    return $this->mapStatus((string) $data['status']);
                }

                return !empty($data['en_invoice']) ? 'delivered' : 'sent';
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
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return $this->sandbox ? 'Super PDP (Sandbox)' : 'Super PDP';
    }

    /**
     * Obtain an OAuth2 access token (client_credentials grant), cached for the instance.
     */
    protected function getAccessToken(): ?string
    {
        if ($this->token) {
            return $this->token;
        }

        $response = Http::asForm()->post($this->endpoint . '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            Log::error('Super PDP OAuth2 token error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        }

        return $this->token = $response->json('access_token');
    }

    /**
     * Map a Super PDP status to our internal status (sent, delivered, failed).
     * TODO (API Super PDP) : aligner sur les statuts réels du cycle de vie.
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
