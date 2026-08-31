<?php

namespace App\Services;

use App\Models\Invoice;
use App\Services\Payment\QrCodePaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
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
        // Always use FR filename for storage to keep paths stable across user locale changes.
        $filename = $this->getFilename($invoice, 'fr');
        $path = 'invoices/' . $filename;

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate PDF preview as HTML.
     */
    public function preview(Invoice $invoice, ?string $localeOverride = null): string
    {
        $this->ensureFinalized($invoice);

        $data = $this->prepareData($invoice, $localeOverride);

        return view('pdf.invoice', $data)->render();
    }

    /**
     * Generate draft preview as HTML (for brouillons).
     */
    public function previewDraft(Invoice $invoice, ?string $localeOverride = null): string
    {
        $data = $this->prepareDraftData($invoice, $localeOverride);

        return view('pdf.invoice', $data)->render();
    }

    /**
     * Download draft invoice as PDF.
     */
    public function downloadDraft(Invoice $invoice, ?string $localeOverride = null): Response
    {
        $pdf = $this->createDraftPdf($invoice, $localeOverride);
        $filename = "brouillon-facture-{$invoice->id}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Stream draft invoice as PDF.
     */
    public function streamDraft(Invoice $invoice, ?string $localeOverride = null): Response
    {
        $pdf = $this->createDraftPdf($invoice, $localeOverride);
        $filename = "brouillon-facture-{$invoice->id}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Create PDF instance for draft invoice.
     */
    protected function createDraftPdf(Invoice $invoice, ?string $localeOverride = null): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareDraftData($invoice, $localeOverride);

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Stream PDF for download.
     */
    public function stream(Invoice $invoice, ?string $localeOverride = null): Response
    {
        $this->ensureFinalized($invoice);

        $pdf = $this->createPdf($invoice, $localeOverride);
        $filename = $this->getFilename($invoice, $localeOverride);

        return $pdf->stream($filename);
    }

    /**
     * Download PDF.
     */
    public function download(Invoice $invoice, ?string $localeOverride = null): Response
    {
        $this->ensureFinalized($invoice);

        $pdf = $this->createPdf($invoice, $localeOverride);
        $filename = $this->getFilename($invoice, $localeOverride);

        return $pdf->download($filename);
    }

    /**
     * Get PDF content as string.
     */
    public function getContent(Invoice $invoice, ?string $localeOverride = null): string
    {
        $this->ensureFinalized($invoice);

        return $this->createPdf($invoice, $localeOverride)->output();
    }

    /**
     * Create PDF instance.
     */
    protected function createPdf(Invoice $invoice, ?string $localeOverride = null): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($invoice, $localeOverride);

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Encaissements à faire figurer sur la facture, du plus ancien au plus récent.
     *
     * Demande d'un client (2026-08-28) : il encaisse un acompte à la signature
     * du devis, puis le solde le jour de la prestation, et n'établit qu'UNE
     * facture — toute la TVA dessus. Ce que sa cliente doit lire, c'est
     * « acompte versé le 12/09 : 300 € — reste à payer : 700 € ».
     *
     * ⚠️ Un acompte n'est pas une remise. Une remise réduirait la base taxable
     * et donc la TVA déclarée ; le prix ne change pas, seul le moment du
     * paiement change. D'où un bloc SOUS le total, jamais une ligne au-dessus.
     *
     * Un encaissement antérieur à la date d'émission est un acompte : il a été
     * reçu avant que la facture n'existe. Les autres sont des règlements.
     *
     * @return array<int, array{libelle: string, montant: float}>
     */
    private function encaissementsAAfficher(Invoice $invoice): array
    {
        return $invoice->payments
            ->sortBy('paid_at')
            ->map(function ($paiement) use ($invoice) {
                $date = $paiement->paid_at?->format('d/m/Y') ?? '';
                $estAcompte = $invoice->issued_at
                    && $paiement->paid_at
                    && $paiement->paid_at->lt($invoice->issued_at);

                return [
                    'libelle' => __($estAcompte ? 'invoice.deposit_paid' : 'invoice.payment_received', ['date' => $date]),
                    'montant' => round((float) $paiement->amount, 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Prepare data for PDF template.
     * IMPORTANT: Uses snapshots for immutability.
     *
     * @param Invoice $invoice
     * @param string|null $localeOverride Optional locale to override client's preference
     */
    public function prepareData(Invoice $invoice, ?string $localeOverride = null): array
    {
        $invoice->load('items', 'discounts', 'payments');

        $seller = $invoice->seller_snapshot ?? [];
        $buyer = $invoice->buyer_snapshot ?? [];

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

        // VAT summary — global discounts ventilated per rate (source unique)
        $documentTotals = app(\App\Services\DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);
        $vatSummary = collect($documentTotals['rates'])
            ->map(fn ($r) => [
                'rate' => $r['rate'],
                'base' => $r['net_base'],
                'vat' => $r['vat'],
            ])
            ->values()
            ->toArray();

        // Get PDF color from seller snapshot or default
        $pdfColor = \App\Models\BusinessSettings::legibleColor($seller['pdf_color'] ?? \App\Models\BusinessSettings::DEFAULT_PDF_COLOR);

        // Mention « Créé avec faktur.lu », réservée au plan gratuit (FEAT-104).
        //
        // Lue dans l'instantané, jamais recalculée : une facture finalisée est
        // un document comptable immuable. La recalculer ferait apparaître ou
        // disparaître la mention sur tout l'historique au moindre changement
        // d'abonnement, et deux exemplaires du même numéro différeraient.
        //
        // Repli pour les factures finalisées avant cette correction, dont
        // l'instantané ne porte pas la clé : on garde l'ancien comportement.
        // Inventer une valeur reviendrait à réécrire l'histoire qu'on protège.
        $showBranding = array_key_exists('show_branding', $seller)
            ? (bool) $seller['show_branding']
            : ($invoice->user ? $invoice->user->isFree() : true);

        // Generate QR codes for payment (not for credit notes)
        $paymentQrCode = null;
        $customPaymentQrCode = null;
        $isCreditNote = $invoice->isCreditNote();
        if (!$isCreditNote && !empty($seller['show_payment_qrcode'])) {
            // EPC QR code (SEPA standard with amount)
            if (!empty($seller['iban'])) {
                $paymentQrCode = $this->generatePaymentQrCode(
                    $seller['company_name'] ?? $seller['legal_name'] ?? '',
                    $seller['iban'],
                    $seller['bic'] ?? '',
                    (float) $invoice->total_ttc,
                    $this->generatePaymentReference($invoice),
                );
            }
            // Custom QR code image (Payconiq, PayPal, etc.)
            if (!empty($seller['payment_qrcode_path'])) {
                $customPaymentQrCode = $this->getLogoDataUri($seller['payment_qrcode_path']);
            }
        }

        return [
            'invoice' => $invoice,
            'seller' => $seller,
            'buyer' => $buyer,
            'items' => $invoice->items,
            'isVatExempt' => $isVatExempt,
            'isCreditNote' => $isCreditNote,
            'vatSummary' => $vatSummary,
            'discounts' => $invoice->discounts,
            'subtotalHt' => $documentTotals['subtotal_ht'],
            // Encaissements déjà reçus, pour que le client lise « acompte
            // versé » et « reste à payer » plutôt que de recalculer (FEAT-114).
            'encaissements' => $this->encaissementsAAfficher($invoice),
            'resteAPayer' => $invoice->amountDue(),
            'paymentReference' => $this->generatePaymentReference($invoice),
            'logoPath' => $logoPath,
            'pdfColor' => $pdfColor,
            // FEAT-109 : dompdf ne gère pas les variables CSS, les tailles
            // sont donc calculées ici et écrites en dur dans le gabarit.
            'fontSize' => \App\Models\BusinessSettings::pdfFontSizer($seller['pdf_text_size'] ?? null),
            'logoScale' => \App\Models\BusinessSettings::pdfLogoScale($seller['pdf_logo_size'] ?? null),
            'showBranding' => $showBranding,
            'locale' => $locale,
            'paymentQrCode' => $paymentQrCode,
            'customPaymentQrCode' => $customPaymentQrCode,
        ];
    }

    /**
     * Prepare data for draft preview (live preview before finalization).
     * Uses current data instead of snapshots.
     *
     * @param Invoice $invoice
     * @param string|null $localeOverride Optional locale to override client's preference
     */
    public function prepareDraftData(Invoice $invoice, ?string $localeOverride = null): array
    {
        $invoice->load(['items', 'client', 'discounts', 'payments']);

        // Get current business settings for seller info
        $settings = \App\Models\BusinessSettings::getInstance();
        $seller = $settings ? [
            'company_name' => $settings->company_name,
            'name' => $settings->legal_name,
            'address' => $settings->address,
            'postal_code' => $settings->postal_code,
            'city' => $settings->city,
            'country' => $settings->country_code ?? 'Luxembourg',
            'country_code' => $settings->country_code ?? 'LU',
            'matricule' => $settings->matricule,
            'rcs_number' => $settings->rcs_number,
            'establishment_authorization' => $settings->establishment_authorization,
            'vat_number' => $settings->vat_number,
            'vat_regime' => $settings->vat_regime,
            'iban' => $settings->iban,
            'bic' => $settings->bic,
            'bank_name' => $settings->bank_name,
            'default_payment_methods' => $settings->getEffectivePaymentMethods(),
            'payment_instructions' => $settings->payment_instructions,
            'show_payment_conditions' => $settings->show_payment_conditions ?? true,
            'late_penalty_text' => $settings->late_penalty_text,
            'recovery_fee_amount' => $settings->recovery_fee_amount,
            'discount_terms' => $settings->discount_terms,
            'email' => $settings->email,
            'show_email_on_invoice' => $settings->show_email_on_invoice,
            'phone' => $settings->phone,
            'show_phone_on_invoice' => $settings->show_phone_on_invoice,
            // FEAT-109 : un brouillon suit le réglage courant. Une facture
            // finalisée, elle, garde celui figé dans son instantané.
            'pdf_text_size' => $settings->pdf_text_size,
            'pdf_logo_size' => $settings->pdf_logo_size,
            'website' => null,
        ] : [];

        // Get logo as base64 data URI for embedding in HTML/PDF
        $logoPath = null;
        if ($settings?->logo_path) {
            $logoPath = $this->getLogoDataUri($settings->logo_path);
        }

        // Get current client data for buyer info
        $client = $invoice->client;
        $locale = $localeOverride ?? $client?->locale ?? 'fr';
        $this->setLocale($locale);

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
            'locale' => $locale,
        ] : [];

        // Determine if VAT exempt (franchise regime)
        $isVatExempt = ($seller['vat_regime'] ?? '') === 'franchise';

        // VAT summary — global discounts ventilated per rate (source unique)
        $documentTotals = app(\App\Services\DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);
        $vatSummary = collect($documentTotals['rates'])
            ->map(fn ($r) => [
                'rate' => $r['rate'],
                'base' => $r['net_base'],
                'vat' => $r['vat'],
            ])
            ->values()
            ->toArray();

        // Get PDF color from settings
        $pdfColor = \App\Models\BusinessSettings::legibleColor($settings?->getEffectivePdfColor() ?? \App\Models\BusinessSettings::DEFAULT_PDF_COLOR);

        // Un brouillon n'est pas figé : il doit montrer ce que donnera la
        // facture avec le plan d'aujourd'hui. Rien à lire dans un instantané
        // qui n'existe pas encore (FEAT-104).
        $showBranding = $invoice->user ? $invoice->user->isFree() : true;

        // Generate QR codes for draft preview
        $paymentQrCode = null;
        $customPaymentQrCode = null;
        $isCreditNote = $invoice->isCreditNote();
        if (!$isCreditNote && $settings?->show_payment_qrcode) {
            if (!empty($settings->iban)) {
                $paymentQrCode = $this->generatePaymentQrCode(
                    $settings->company_name ?? $settings->legal_name ?? '',
                    $settings->iban,
                    $settings->bic ?? '',
                    (float) $invoice->total_ttc,
                    $invoice->number ?? '',
                );
            }
            if ($settings->payment_qrcode_path) {
                $customPaymentQrCode = $this->getLogoDataUri($settings->payment_qrcode_path);
            }
        }

        return [
            'invoice' => $invoice,
            // Le brouillon rend le même gabarit : sans ces clés, l'aperçu
            // mentirait sur ce que la facture finalisée montrera.
            'encaissements' => $this->encaissementsAAfficher($invoice),
            'resteAPayer' => $invoice->amountDue(),
            'seller' => $seller,
            'buyer' => $buyer,
            'items' => $invoice->items,
            'isVatExempt' => $isVatExempt,
            'isCreditNote' => $isCreditNote,
            'vatSummary' => $vatSummary,
            'discounts' => $invoice->discounts,
            'subtotalHt' => $documentTotals['subtotal_ht'],
            'paymentReference' => 'BROUILLON',
            'logoPath' => $logoPath,
            'pdfColor' => $pdfColor,
            // FEAT-109 : dompdf ne gère pas les variables CSS, les tailles
            // sont donc calculées ici et écrites en dur dans le gabarit.
            'fontSize' => \App\Models\BusinessSettings::pdfFontSizer($seller['pdf_text_size'] ?? null),
            'logoScale' => \App\Models\BusinessSettings::pdfLogoScale($seller['pdf_logo_size'] ?? null),
            'showBranding' => $showBranding,
            'locale' => $locale,
            'paymentQrCode' => $paymentQrCode,
            'customPaymentQrCode' => $customPaymentQrCode,
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
    protected function getFilename(Invoice $invoice, ?string $localeOverride = null): string
    {
        $locale = $localeOverride ?? app()->getLocale();
        $isCredit = $invoice->isCreditNote();

        $invoiceWords = [
            'fr' => 'facture', 'de' => 'rechnung', 'en' => 'invoice', 'lb' => 'rechnung', 'pt' => 'fatura',
        ];
        $creditWords = [
            'fr' => 'avoir', 'de' => 'gutschrift', 'en' => 'credit-note', 'lb' => 'gutschrëft', 'pt' => 'nota-credito',
        ];

        $words = $isCredit ? $creditWords : $invoiceWords;
        $type = $words[$locale] ?? $words['fr'];

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
     * Generate EPC QR code for payment.
     */
    protected function generatePaymentQrCode(
        string $beneficiaryName,
        string $iban,
        string $bic,
        float $amount,
        string $reference,
    ): ?string {
        return app(QrCodePaymentService::class)->generateEpcQrCode(
            $beneficiaryName,
            $iban,
            $bic,
            $amount,
            $reference,
        );
    }

    /**
     * Ensure invoice is finalized.
     */
    protected function ensureFinalized(Invoice $invoice): void
    {
        if (!$invoice->isFinalized()) {
            throw new \InvalidArgumentException(
                __('app.error_pdf_not_finalized')
            );
        }
    }

    /**
     * Set application locale for PDF generation.
     */
    protected function setLocale(string $locale): void
    {
        $supportedLocales = ['fr', 'de', 'en', 'lb', 'pt'];

        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        }
    }
}
