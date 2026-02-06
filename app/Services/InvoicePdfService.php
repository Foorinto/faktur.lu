<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Generate PDF and save to storage.
     */
    public function generate(Invoice $invoice): string
    {
        $this->ensureFinalized($invoice);

        $pdf = $this->createPdf($invoice);
        $filename = $this->getFilename($invoice);
        $path = 'invoices/' . $filename;

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate PDF preview as HTML.
     */
    public function preview(Invoice $invoice): string
    {
        $this->ensureFinalized($invoice);

        $data = $this->prepareData($invoice);

        return view('pdf.invoice', $data)->render();
    }

    /**
     * Generate draft preview as HTML (for brouillons).
     */
    public function previewDraft(Invoice $invoice): string
    {
        $data = $this->prepareDraftData($invoice);

        return view('pdf.invoice', $data)->render();
    }

    /**
     * Download draft invoice as PDF.
     */
    public function downloadDraft(Invoice $invoice): Response
    {
        $pdf = $this->createDraftPdf($invoice);
        $filename = "brouillon-facture-{$invoice->id}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Stream draft invoice as PDF.
     */
    public function streamDraft(Invoice $invoice): Response
    {
        $pdf = $this->createDraftPdf($invoice);
        $filename = "brouillon-facture-{$invoice->id}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Create PDF instance for draft invoice.
     */
    protected function createDraftPdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareDraftData($invoice);

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Stream PDF for download.
     */
    public function stream(Invoice $invoice): Response
    {
        $this->ensureFinalized($invoice);

        $pdf = $this->createPdf($invoice);
        $filename = $this->getFilename($invoice);

        return $pdf->stream($filename);
    }

    /**
     * Download PDF.
     */
    public function download(Invoice $invoice): Response
    {
        $this->ensureFinalized($invoice);

        $pdf = $this->createPdf($invoice);
        $filename = $this->getFilename($invoice);

        return $pdf->download($filename);
    }

    /**
     * Get PDF content as string.
     */
    public function getContent(Invoice $invoice): string
    {
        $this->ensureFinalized($invoice);

        return $this->createPdf($invoice)->output();
    }

    /**
     * Create PDF instance.
     */
    protected function createPdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($invoice);

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Prepare data for PDF template.
     * IMPORTANT: Uses snapshots for immutability.
     */
    public function prepareData(Invoice $invoice): array
    {
        $invoice->load('items');

        $seller = $invoice->seller_snapshot ?? [];
        $buyer = $invoice->buyer_snapshot ?? [];

        // Determine if VAT exempt (franchise regime)
        $isVatExempt = ($seller['vat_regime'] ?? '') === 'franchise';

        // Get logo as base64 data URI for embedding in HTML/PDF
        $logoPath = null;
        if (!empty($seller['logo_path'])) {
            $logoPath = $this->getLogoDataUri($seller['logo_path']);
        }

        // Group items by VAT rate for summary
        $vatSummary = $invoice->items
            ->groupBy('vat_rate')
            ->map(function ($items, $rate) {
                return [
                    'rate' => $rate,
                    'base' => $items->sum('total_ht'),
                    'vat' => $items->sum('total_vat'),
                ];
            })
            ->values()
            ->toArray();

        // Get PDF color from seller snapshot or default
        $pdfColor = $seller['pdf_color'] ?? \App\Models\BusinessSettings::DEFAULT_PDF_COLOR;

        // Show branding for Starter (free) users
        $showBranding = $invoice->user ? $invoice->user->isStarter() : true;

        return [
            'invoice' => $invoice,
            'seller' => $seller,
            'buyer' => $buyer,
            'items' => $invoice->items,
            'isVatExempt' => $isVatExempt,
            'isCreditNote' => $invoice->isCreditNote(),
            'vatSummary' => $vatSummary,
            'paymentReference' => $this->generatePaymentReference($invoice),
            'logoPath' => $logoPath,
            'pdfColor' => $pdfColor,
            'showBranding' => $showBranding,
        ];
    }

    /**
     * Prepare data for draft preview (live preview before finalization).
     * Uses current data instead of snapshots.
     */
    public function prepareDraftData(Invoice $invoice): array
    {
        $invoice->load(['items', 'client']);

        // Get current business settings for seller info
        $settings = \App\Models\BusinessSettings::getInstance();
        $seller = $settings ? [
            'company_name' => $settings->company_name,
            'name' => $settings->legal_name,
            'address' => $settings->address,
            'postal_code' => $settings->postal_code,
            'city' => $settings->city,
            'country' => $settings->country_code ?? 'Luxembourg',
            'matricule' => $settings->matricule,
            'rcs_number' => $settings->rcs_number,
            'establishment_authorization' => $settings->establishment_authorization,
            'vat_number' => $settings->vat_number,
            'vat_regime' => $settings->vat_regime,
            'iban' => $settings->iban,
            'bic' => $settings->bic,
            'bank_name' => $settings->bank_name,
            'email' => $settings->email,
            'show_email_on_invoice' => $settings->show_email_on_invoice,
            'phone' => $settings->phone,
            'show_phone_on_invoice' => $settings->show_phone_on_invoice,
            'website' => null,
        ] : [];

        // Get logo as base64 data URI for embedding in HTML/PDF
        $logoPath = null;
        if ($settings?->logo_path) {
            $logoPath = $this->getLogoDataUri($settings->logo_path);
        }

        // Get current client data for buyer info
        $client = $invoice->client;
        $buyer = $client ? [
            'company_name' => $client->name,
            'name' => $client->name,
            'contact_name' => $client->contact_name,
            'address' => $client->address,
            'postal_code' => $client->postal_code,
            'city' => $client->city,
            'country' => $client->country,
            'vat_number' => $client->vat_number,
            'registration_number' => $client->registration_number,
            'email' => $client->email,
        ] : [];

        // Determine if VAT exempt (franchise regime)
        $isVatExempt = ($seller['vat_regime'] ?? '') === 'franchise';

        // Group items by VAT rate for summary
        $vatSummary = $invoice->items
            ->groupBy('vat_rate')
            ->map(function ($items, $rate) {
                return [
                    'rate' => $rate,
                    'base' => $items->sum('total_ht'),
                    'vat' => $items->sum('total_vat'),
                ];
            })
            ->values()
            ->toArray();

        // Get PDF color from settings
        $pdfColor = $settings?->getEffectivePdfColor() ?? \App\Models\BusinessSettings::DEFAULT_PDF_COLOR;

        // Show branding for Starter (free) users
        $showBranding = $invoice->user ? $invoice->user->isStarter() : true;

        return [
            'invoice' => $invoice,
            'seller' => $seller,
            'buyer' => $buyer,
            'items' => $invoice->items,
            'isVatExempt' => $isVatExempt,
            'isCreditNote' => $invoice->isCreditNote(),
            'vatSummary' => $vatSummary,
            'paymentReference' => 'BROUILLON',
            'logoPath' => $logoPath,
            'pdfColor' => $pdfColor,
            'showBranding' => $showBranding,
        ];
    }

    /**
     * Generate payment reference for bank transfer.
     */
    protected function generatePaymentReference(Invoice $invoice): string
    {
        $clientName = $invoice->buyer_snapshot['company_name']
            ?? $invoice->buyer_snapshot['name']
            ?? 'CLIENT';

        // Take first 10 chars of company name, uppercase, no special chars
        $clientRef = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $clientName));
        $clientRef = substr($clientRef, 0, 10);

        return $invoice->number . '-' . $clientRef;
    }

    /**
     * Get PDF filename.
     */
    protected function getFilename(Invoice $invoice): string
    {
        $type = $invoice->isCreditNote() ? 'avoir' : 'facture';
        return "{$type}-{$invoice->number}.pdf";
    }

    /**
     * Convert logo file to base64 data URI.
     */
    protected function getLogoDataUri(string $logoPath): ?string
    {
        $fullPath = storage_path('app/public/' . $logoPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    /**
     * Ensure invoice is finalized.
     */
    protected function ensureFinalized(Invoice $invoice): void
    {
        if (!$invoice->isFinalized()) {
            throw new \InvalidArgumentException(
                'Impossible de générer un PDF pour une facture non finalisée.'
            );
        }
    }
}
