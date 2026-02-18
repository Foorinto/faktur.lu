<?php

namespace App\Services\Peppol;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Storecove Access Point integration.
 * https://www.storecove.com/docs/
 */
class StorecoveService implements PeppolAccessPointInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected ?string $legalEntityId;
    protected bool $sandbox;

    public function __construct()
    {
        $this->apiKey = config('peppol.storecove.api_key', '');
        $this->apiUrl = config('peppol.storecove.api_url', 'https://api.storecove.com/api/v2');
        $this->legalEntityId = config('peppol.storecove.legal_entity_id');
        $this->sandbox = config('peppol.storecove.sandbox', true);
    }

    /**
     * Send an invoice via Storecove.
     */
    public function sendInvoice(Invoice $invoice, string $peppolXml): PeppolTransmissionResult
    {
        if (!$this->isConfigured()) {
            return PeppolTransmissionResult::failure('Storecove n\'est pas configuré correctement.');
        }

        try {
            $buyer = $invoice->buyer;
            $recipientId = $buyer['peppol_endpoint_id'] ?? null;
            $recipientScheme = $buyer['peppol_endpoint_scheme'] ?? null;

            if (!$recipientId || !$recipientScheme) {
                return PeppolTransmissionResult::failure('Le destinataire n\'a pas d\'identifiant Peppol configuré.');
            }

            // Prepare the document submission
            $payload = [
                'legalEntityId' => (int) $this->legalEntityId,
                'routing' => [
                    'eIdentifiers' => [
                        [
                            'scheme' => $this->mapSchemeToStorecove($recipientScheme),
                            'id' => $recipientId,
                        ],
                    ],
                ],
                'document' => [
                    'documentType' => $invoice->isCreditNote() ? 'creditnote' : 'invoice',
                    'rawDocumentData' => [
                        'document' => base64_encode($peppolXml),
                        'parseStrategy' => 'ubl',
                    ],
                ],
            ];

            Log::info('Sending invoice to Storecove', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'recipient_id' => $recipientId,
                'sandbox' => $this->sandbox,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/document_submissions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $documentId = (string) ($data['guid'] ?? $data['id'] ?? '');

                Log::info('Invoice sent successfully via Storecove', [
                    'invoice_id' => $invoice->id,
                    'document_id' => $documentId,
                ]);

                return PeppolTransmissionResult::success($documentId, [
                    'storecove_response' => $data,
                    'sandbox' => $this->sandbox,
                ]);
            }

            $errorData = $response->json();
            $errorMessage = $this->parseStorecoveError($errorData);

            Log::error('Storecove API error', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            return PeppolTransmissionResult::failure($errorMessage, [
                'storecove_error' => $errorData,
                'http_status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error('Storecove exception', [
                'invoice_id' => $invoice->id,
                'exception' => $e->getMessage(),
            ]);

            return PeppolTransmissionResult::failure(
                'Erreur de connexion à Storecove: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the status of a document submission.
     */
    public function getTransmissionStatus(string $documentId): string
    {
        if (!$this->isConfigured()) {
            return 'unknown';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/document_submissions/' . $documentId);

            if ($response->successful()) {
                $data = $response->json();
                return $this->mapStorecoveStatus($data['status'] ?? 'unknown');
            }

            return 'unknown';
        } catch (\Exception $e) {
            Log::error('Storecove status check failed', [
                'document_id' => $documentId,
                'exception' => $e->getMessage(),
            ]);

            return 'unknown';
        }
    }

    /**
     * Check if Storecove is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->legalEntityId);
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return $this->sandbox ? 'Storecove (Sandbox)' : 'Storecove';
    }

    /**
     * Map our scheme codes to Storecove scheme format.
     */
    protected function mapSchemeToStorecove(string $scheme): string
    {
        // Storecove uses different scheme identifiers
        // See: https://www.storecove.com/docs/#_peppol_identifiers
        $mapping = [
            '9934' => 'LU:VAT',      // Luxembourg VAT
            '0208' => 'BE:EN',       // Belgium Enterprise Number
            '0009' => 'FR:SIRET',    // France SIRET
            '9930' => 'DE:VAT',      // Germany VAT
            '0106' => 'NL:KVK',      // Netherlands KVK
            '9914' => 'AT:VAT',      // Austria VAT
            '0211' => 'IT:IVA',      // Italy IVA
            '9920' => 'ES:VAT',      // Spain VAT
        ];

        return $mapping[$scheme] ?? $scheme;
    }

    /**
     * Map Storecove status to our status.
     */
    protected function mapStorecoveStatus(string $storecoveStatus): string
    {
        return match (strtolower($storecoveStatus)) {
            'pending', 'queued' => 'sent',
            'sent', 'delivered' => 'delivered',
            'failed', 'rejected' => 'failed',
            default => 'sent',
        };
    }

    /**
     * Parse Storecove error response into a readable message.
     */
    protected function parseStorecoveError(array $errorData): string
    {
        if (isset($errorData['message'])) {
            return $errorData['message'];
        }

        if (isset($errorData['errors']) && is_array($errorData['errors'])) {
            $messages = [];
            foreach ($errorData['errors'] as $error) {
                if (is_array($error)) {
                    $messages[] = $error['message'] ?? $error['detail'] ?? json_encode($error);
                } else {
                    $messages[] = (string) $error;
                }
            }
            return implode('; ', $messages);
        }

        return 'Erreur inconnue de l\'API Storecove';
    }
}
