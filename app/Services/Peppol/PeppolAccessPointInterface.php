<?php

namespace App\Services\Peppol;

use App\Models\Invoice;

interface PeppolAccessPointInterface
{
    /**
     * Send an invoice via the Peppol network.
     *
     * @param Invoice $invoice The invoice to send
     * @param string $peppolXml The Peppol BIS 3.0 XML content
     * @return PeppolTransmissionResult The result of the transmission
     */
    public function sendInvoice(Invoice $invoice, string $peppolXml): PeppolTransmissionResult;

    /**
     * Get the current status of a transmission.
     *
     * @param string $documentId The document ID returned by the Access Point
     * @return string The current status (sent, delivered, failed)
     */
    public function getTransmissionStatus(string $documentId): string;

    /**
     * Check if the Access Point is properly configured.
     *
     * @return bool True if configured and ready to use
     */
    public function isConfigured(): bool;

    /**
     * Get the provider name (for logging and display).
     *
     * @return string The provider name (e.g., "Storecove", "Simulation")
     */
    public function getProviderName(): string;
}
