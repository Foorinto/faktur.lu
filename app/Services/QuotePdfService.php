<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    /**
     * Generate PDF and save to storage.
     */
    public function generate(Quote $quote): string
    {
        $pdf = $this->createPdf($quote);
        $filename = $this->getFilename($quote);
        $path = 'quotes/' . $filename;

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate PDF preview as HTML.
     */
    public function preview(Quote $quote, ?string $localeOverride = null): string
    {
        $data = $this->prepareData($quote, $localeOverride);

        return view('pdf.quote', $data)->render();
    }

    /**
     * Stream PDF for download.
     */
    public function stream(Quote $quote, ?string $localeOverride = null): Response
    {
        $pdf = $this->createPdf($quote, $localeOverride);
        $filename = $this->getFilename($quote);

        return $pdf->stream($filename);
    }

    /**
     * Download PDF.
     */
    public function download(Quote $quote, ?string $localeOverride = null): Response
    {
        $pdf = $this->createPdf($quote, $localeOverride);
        $filename = $this->getFilename($quote);

        return $pdf->download($filename);
    }

    /**
     * Get PDF content as string.
     */
    public function getContent(Quote $quote, ?string $localeOverride = null): string
    {
        return $this->createPdf($quote, $localeOverride)->output();
    }

    /**
     * Create PDF instance.
     */
    protected function createPdf(Quote $quote, ?string $localeOverride = null): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($quote, $localeOverride);

        return Pdf::loadView('pdf.quote', $data)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Prepare data for PDF template.
     *
     * @param Quote $quote
     * @param string|null $localeOverride Optional locale to override client's preference
     */
    public function prepareData(Quote $quote, ?string $localeOverride = null): array
    {
        $quote->load(['items', 'client']);

        // Use snapshots if they exist, otherwise use current data
        if ($quote->seller_snapshot) {
            $seller = $quote->seller_snapshot;
        } else {
            $settings = BusinessSettings::getInstance();
            $seller = $settings ? [
                'company_name' => $settings->company_name,
                'name' => $settings->legal_name,
                'address' => $settings->address,
                'postal_code' => $settings->postal_code,
                'city' => $settings->city,
                'country' => $settings->country_code ?? 'Luxembourg',
                'matricule' => $settings->matricule,
                'vat_number' => $settings->vat_number,
                'vat_regime' => $settings->vat_regime,
                'iban' => $settings->iban,
                'bic' => $settings->bic,
                'bank_name' => $settings->bank_name,
                'email' => $settings->email,
                'phone' => $settings->phone,
                'website' => null,
                'logo_path' => $settings->logo_path,
            ] : [];
        }

        if ($quote->buyer_snapshot) {
            $buyer = $quote->buyer_snapshot;
        } else {
            $client = $quote->client;
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
                'locale' => $client->locale ?? 'fr',
            ] : [];
        }

        // Set locale based on override or client's preference
        $locale = $localeOverride ?? $buyer['locale'] ?? 'fr';
        $this->setLocale($locale);

        // Determine if VAT exempt (franchise regime)
        $isVatExempt = ($seller['vat_regime'] ?? '') === 'franchise';

        // Get logo as base64 data URI for embedding in HTML/PDF
        $logoPath = null;
        if (!empty($seller['logo_path'])) {
            $logoPath = $this->getLogoDataUri($seller['logo_path']);
        }

        // Group items by VAT rate for summary
        $vatSummary = $quote->items
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

        // Show branding for Starter (free) users
        $showBranding = $quote->user ? $quote->user->isStarter() : true;

        // Get VAT mention text
        $vatMentionText = $quote->getVatMentionText();

        // Get footer message (from quote or default)
        $footerMessage = $quote->footer_message;
        if (empty($footerMessage)) {
            $settings = BusinessSettings::getInstance();
            $footerMessage = $settings?->default_invoice_footer ?? 'Merci pour votre confiance !';
        }

        return [
            'quote' => $quote,
            'seller' => $seller,
            'buyer' => $buyer,
            'items' => $quote->items,
            'isVatExempt' => $isVatExempt,
            'vatSummary' => $vatSummary,
            'logoPath' => $logoPath,
            'showBranding' => $showBranding,
            'locale' => $locale,
            'vatMentionText' => $vatMentionText,
            'footerMessage' => $footerMessage,
        ];
    }

    /**
     * Set application locale for PDF generation.
     */
    protected function setLocale(string $locale): void
    {
        $supportedLocales = ['fr', 'de', 'en', 'lb'];

        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        }
    }

    /**
     * Get PDF filename.
     */
    protected function getFilename(Quote $quote): string
    {
        return "devis-{$quote->reference}.pdf";
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
}
