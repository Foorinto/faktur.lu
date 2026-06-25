<?php

namespace App\Services\Peppol;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Super PDP - Plateforme Agréée (France) + Access Point Peppol.
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

            // POST /v1.beta/invoices - corps = XML UBL brut.
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

                // Le statut est porté par le DERNIER événement du cycle de vie
                // (data.events[].status_code, ex. "api:uploaded", "fr:204"…).
                $events = $data['events'] ?? [];
                if (!empty($events)) {
                    $last = end($events);
                    return $this->mapStatus((string) ($last['status_code'] ?? ''));
                }

                return 'sent';
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
     * Map a Super PDP lifecycle status_code (ex. "api:uploaded", "fr:204") to
     * our internal status (sent, delivered, failed).
     * Codes AFNOR/PPF : 202 mise à disposition, 203 prise en charge, 204 approuvée,
     * 208 refusée… + codes "api:" propres à Super PDP.
     */
    protected function mapStatus(string $statusCode): string
    {
        $code = strtolower($statusCode);

        if (str_contains($code, 'reject') || str_contains($code, 'refus') || str_contains($code, 'error')
            || str_contains($code, ':208') || str_contains($code, ':206')) {
            return 'failed';
        }

        if (str_contains($code, 'deliver') || str_contains($code, 'accept') || str_contains($code, 'received')
            || str_contains($code, ':202') || str_contains($code, ':203') || str_contains($code, ':204')) {
            return 'delivered';
        }

        // Par défaut : envoyée (uploaded, déposée, en cours de traitement…).
        return 'sent';
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
