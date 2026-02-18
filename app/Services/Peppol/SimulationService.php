<?php

namespace App\Services\Peppol;

use App\Models\Invoice;
use Illuminate\Support\Str;

/**
 * Simulation service for development and testing.
 * Simulates Peppol transmission without a real Access Point.
 */
class SimulationService implements PeppolAccessPointInterface
{
    protected int $delaySeconds;
    protected float $successRate;

    public function __construct()
    {
        $this->delaySeconds = config('peppol.simulation.delay_seconds', 2);
        $this->successRate = config('peppol.simulation.success_rate', 0.95);
    }

    /**
     * Simulate sending an invoice via Peppol.
     */
    public function sendInvoice(Invoice $invoice, string $peppolXml): PeppolTransmissionResult
    {
        // Simulate network delay
        if ($this->delaySeconds > 0) {
            sleep($this->delaySeconds);
        }

        // Simulate random failures based on success rate
        $random = mt_rand(1, 100) / 100;

        if ($random > $this->successRate) {
            $errors = [
                'RECIPIENT_NOT_FOUND' => 'Le destinataire Peppol n\'a pas été trouvé dans le réseau.',
                'INVALID_DOCUMENT' => 'Le document XML n\'est pas valide selon le schéma Peppol BIS 3.0.',
                'NETWORK_ERROR' => 'Erreur de connexion au réseau Peppol.',
                'ENDPOINT_UNAVAILABLE' => 'Le point d\'accès du destinataire est temporairement indisponible.',
            ];

            $errorCode = array_rand($errors);
            $errorMessage = $errors[$errorCode];

            return PeppolTransmissionResult::failure($errorMessage, [
                'error_code' => $errorCode,
                'simulation' => true,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Generate a simulated document ID
        $documentId = 'SIM-' . Str::upper(Str::random(8)) . '-' . now()->format('YmdHis');

        return PeppolTransmissionResult::success($documentId, [
            'simulation' => true,
            'timestamp' => now()->toIso8601String(),
            'invoice_number' => $invoice->number,
            'recipient_id' => $invoice->buyer['peppol_endpoint_id'] ?? null,
            'message' => 'Document accepted for delivery (simulation mode)',
        ]);
    }

    /**
     * Simulate getting transmission status.
     */
    public function getTransmissionStatus(string $documentId): string
    {
        // In simulation mode, always return delivered after initial send
        return 'delivered';
    }

    /**
     * Check if simulation service is configured.
     */
    public function isConfigured(): bool
    {
        // Simulation is always configured
        return true;
    }

    /**
     * Get the provider name.
     */
    public function getProviderName(): string
    {
        return 'Simulation';
    }
}
