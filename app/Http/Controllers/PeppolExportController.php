<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PeppolExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PeppolExportController extends Controller
{
    public function __construct(
        protected PeppolExportService $peppolService
    ) {}

    /**
     * Export an invoice to Peppol BIS 3.0 XML format.
     */
    public function export(Request $request, Invoice $invoice): Response
    {
        // Ensure invoice belongs to authenticated user
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        // Check invoice is finalized
        if ($invoice->isDraft()) {
            return back()->with('error', 'Seules les factures finalisées peuvent être exportées au format Peppol.');
        }

        // Check seller has Peppol endpoint
        $seller = $invoice->seller;
        if (empty($seller['peppol_endpoint_id']) || empty($seller['peppol_endpoint_scheme'])) {
            return back()->with('error', 'Veuillez configurer votre identifiant Peppol dans les paramètres entreprise.');
        }

        // Check buyer has Peppol endpoint
        $buyer = $invoice->buyer;
        if (empty($buyer['peppol_endpoint_id']) || empty($buyer['peppol_endpoint_scheme'])) {
            return back()->with('error', 'Le client doit avoir un identifiant Peppol configuré.');
        }

        // Generate XML
        $xml = $this->peppolService->generate($invoice);
        $filename = $this->peppolService->getFilename($invoice);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
