<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ToolsController extends Controller
{
    /**
     * Page hub listant tous les outils gratuits.
     */
    public function index(): Response
    {
        return Inertia::render('Tools/Index');
    }

    /**
     * Calculateur TVA Luxembourg.
     */
    public function vatCalculator(): Response
    {
        return Inertia::render('Tools/VatCalculator');
    }

    /**
     * Simulateur franchise TVA Luxembourg.
     */
    public function vatExemption(): Response
    {
        return Inertia::render('Tools/VatExemption');
    }

    /**
     * Validateur IBAN luxembourgeois.
     */
    public function ibanValidator(): Response
    {
        return Inertia::render('Tools/IbanValidator');
    }

    /**
     * Générateur de facture express sans compte.
     */
    public function invoiceGenerator(): Response
    {
        return Inertia::render('Tools/InvoiceGenerator');
    }

    /**
     * Page hub des templates téléchargeables avec capture email.
     */
    public function templates(): Response
    {
        return Inertia::render('Tools/Templates');
    }

    /**
     * Telecharge un template apres capture de l'email.
     */
    public function downloadTemplate(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'template' => 'required|string|in:invoice_blank,aed_checklist,reminder_letter,vat_calendar',
            'language' => 'nullable|string|in:fr,de,en,lb,pt',
        ]);

        $language = $validated['language'] ?? app()->getLocale();
        $template = $validated['template'];

        // Capturer l'email comme subscriber newsletter (source = templates)
        NewsletterSubscriber::subscribe(
            $validated['email'],
            $language,
            'templates_' . $template
        );

        // Générer le PDF correspondant
        $views = [
            'invoice_blank' => 'pdf.templates.invoice-blank',
            'aed_checklist' => 'pdf.templates.aed-checklist',
            'reminder_letter' => 'pdf.templates.reminder-letter',
            'vat_calendar' => 'pdf.templates.vat-calendar',
        ];

        $filenames = [
            'invoice_blank' => [
                'fr' => 'modele-facture-luxembourg',
                'de' => 'rechnungsvorlage-luxemburg',
                'en' => 'luxembourg-invoice-template',
                'lb' => 'rechnungsmodell-letzebuerg',
                'pt' => 'modelo-fatura-luxemburgo',
            ],
            'aed_checklist' => [
                'fr' => 'checklist-controle-aed',
                'de' => 'checkliste-aed-steuerpruefung',
                'en' => 'aed-tax-audit-checklist',
                'lb' => 'checklist-aed-steierpruefung',
                'pt' => 'checklist-inspecao-fiscal-aed',
            ],
            'reminder_letter' => [
                'fr' => 'modele-lettre-relance-impaye',
                'de' => 'mahnschreiben-vorlage',
                'en' => 'payment-reminder-letter-template',
                'lb' => 'mahnungsmodell',
                'pt' => 'modelo-carta-cobranca',
            ],
            'vat_calendar' => [
                'fr' => 'calendrier-tva-luxembourg-2026',
                'de' => 'mwst-kalender-luxemburg-2026',
                'en' => 'luxembourg-vat-calendar-2026',
                'lb' => 'tva-kalenner-letzebuerg-2026',
                'pt' => 'calendario-iva-luxemburgo-2026',
            ],
        ];

        $pdf = Pdf::loadView($views[$template], [
            'language' => $language,
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);

        $filename = $filenames[$template][$language] ?? $filenames[$template]['fr'];

        return $pdf->download($filename . '.pdf');
    }

    /**
     * Génère le PDF de la facture express.
     * Pas d'authentification, pas de stockage. Rate-limited.
     */
    public function generateInvoicePdf(Request $request)
    {
        $validated = $request->validate([
            'sender' => 'required|array',
            'sender.name' => 'required|string|max:200',
            'sender.address' => 'nullable|string|max:500',
            'sender.vat_number' => 'nullable|string|max:50',
            'sender.iban' => 'nullable|string|max:50',
            'sender.bic' => 'nullable|string|max:20',
            'sender.email' => 'nullable|email|max:255',
            'sender.phone' => 'nullable|string|max:50',

            'client' => 'required|array',
            'client.name' => 'required|string|max:200',
            'client.address' => 'nullable|string|max:500',
            'client.vat_number' => 'nullable|string|max:50',
            'client.country' => 'nullable|string|max:5',

            'invoice' => 'required|array',
            'invoice.number' => 'required|string|max:50',
            'invoice.date' => 'required|date',
            'invoice.due_date' => 'nullable|date',
            'invoice.notes' => 'nullable|string|max:2000',

            'lines' => 'required|array|min:1|max:30',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.vat_rate' => 'required|numeric|in:0,3,8,14,17',

            'currency' => 'nullable|string|in:EUR,USD,GBP,CHF',
            'language' => 'nullable|string|in:fr,de,en,lb,pt',
            'reverse_charge' => 'nullable|boolean',
        ]);

        $currency = $validated['currency'] ?? 'EUR';
        $language = $validated['language'] ?? app()->getLocale();
        $reverseCharge = $validated['reverse_charge'] ?? false;

        // Calculs des totaux
        $totalHt = 0;
        $totalVat = 0;
        $vatBreakdown = []; // [taux => ['ht' => x, 'tva' => y]]

        foreach ($validated['lines'] as $line) {
            $lineHt = $line['quantity'] * $line['unit_price'];
            $rate = $reverseCharge ? 0 : (float) $line['vat_rate'];
            $lineVat = $lineHt * ($rate / 100);

            $totalHt += $lineHt;
            $totalVat += $lineVat;

            $rateKey = (string) $rate;
            if (!isset($vatBreakdown[$rateKey])) {
                $vatBreakdown[$rateKey] = ['ht' => 0, 'tva' => 0];
            }
            $vatBreakdown[$rateKey]['ht'] += $lineHt;
            $vatBreakdown[$rateKey]['tva'] += $lineVat;
        }

        $totalTtc = $totalHt + $totalVat;

        $pdf = Pdf::loadView('pdf.express-invoice', [
            'sender' => $validated['sender'],
            'client' => $validated['client'],
            'invoice' => $validated['invoice'],
            'lines' => $validated['lines'],
            'currency' => $currency,
            'language' => $language,
            'reverseCharge' => $reverseCharge,
            'totalHt' => $totalHt,
            'totalVat' => $totalVat,
            'totalTtc' => $totalTtc,
            'vatBreakdown' => $vatBreakdown,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);

        $invoicePrefixes = [
            'fr' => 'facture',
            'de' => 'rechnung',
            'en' => 'invoice',
            'lb' => 'rechnung',
            'pt' => 'fatura',
        ];
        $prefix = $invoicePrefixes[$language] ?? 'facture';
        $filename = sprintf('%s-%s.pdf', $prefix, preg_replace('/[^a-zA-Z0-9-_]/', '_', $validated['invoice']['number']));

        return $pdf->download($filename);
    }
}
