<?php

namespace App\Jobs;

use App\Models\PeppolTransmission;
use App\Services\AuditLogger;
use App\Services\Peppol\PeppolAccessPointInterface;
use App\Services\PeppolExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPeppolInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PeppolTransmission $transmission
    ) {
        $this->tries = config('peppol.retry.max_attempts', 3);
        $this->backoff = config('peppol.retry.backoff_seconds', 60);
    }

    /**
     * Execute the job.
     */
    public function handle(
        PeppolExportService $exportService,
        PeppolAccessPointInterface $accessPoint
    ): void {
        $invoice = $this->transmission->invoice;

        if (!$invoice) {
            Log::error('SendPeppolInvoiceJob: Invoice not found', [
                'transmission_id' => $this->transmission->id,
            ]);
            $this->transmission->markAsFailed('Facture introuvable.');
            return;
        }

        // Mark as processing
        $this->transmission->markAsProcessing();

        Log::info('Processing Peppol transmission', [
            'transmission_id' => $this->transmission->id,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'provider' => $accessPoint->getProviderName(),
        ]);

        try {
            // Generate Peppol XML
            $peppolXml = $exportService->generate($invoice);

            // Send via Access Point
            $result = $accessPoint->sendInvoice($invoice, $peppolXml);

            if ($result->isSuccess()) {
                $this->transmission->markAsSent($result->documentId, $result->responseData);

                Log::info('Peppol transmission successful', [
                    'transmission_id' => $this->transmission->id,
                    'document_id' => $result->documentId,
                ]);

                // Audit log
                AuditLogger::logCustomAction(
                    'peppol_sent',
                    $invoice,
                    [
                        'document_id' => $result->documentId,
                        'provider' => $accessPoint->getProviderName(),
                        'recipient_id' => $this->transmission->recipient_id,
                    ]
                );
            } else {
                $this->transmission->markAsFailed($result->errorMessage, $result->responseData);

                Log::warning('Peppol transmission failed', [
                    'transmission_id' => $this->transmission->id,
                    'error' => $result->errorMessage,
                ]);

                // Don't retry if it's a permanent error
                if ($this->isPermanentError($result->errorMessage)) {
                    $this->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error('Peppol transmission exception', [
                'transmission_id' => $this->transmission->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->transmission->markAsFailed('Erreur système: ' . $e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPeppolInvoiceJob failed permanently', [
            'transmission_id' => $this->transmission->id,
            'exception' => $exception->getMessage(),
        ]);

        // Ensure transmission is marked as failed
        if (!$this->transmission->hasFailed()) {
            $this->transmission->markAsFailed(
                'Échec après ' . $this->tries . ' tentatives: ' . $exception->getMessage()
            );
        }

        // Audit log
        $invoice = $this->transmission->invoice;
        if ($invoice) {
            AuditLogger::logCustomAction(
                'peppol_failed',
                $invoice,
                [
                    'error' => $exception->getMessage(),
                    'attempts' => $this->tries,
                ]
            );
        }
    }

    /**
     * Determine if the error is permanent and should not be retried.
     */
    protected function isPermanentError(string $errorMessage): bool
    {
        $permanentErrors = [
            'RECIPIENT_NOT_FOUND',
            'INVALID_DOCUMENT',
            'identifiant Peppol',
        ];

        foreach ($permanentErrors as $error) {
            if (str_contains($errorMessage, $error)) {
                return true;
            }
        }

        return false;
    }
}
