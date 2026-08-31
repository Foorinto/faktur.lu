<?php

namespace App\Actions;

use App\Exceptions\ImmutableInvoiceException;
use App\Models\BusinessSettings;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeInvoiceAction
{
    public function __construct(
        private GenerateInvoiceNumberAction $generateNumber,
        private CalculateInvoiceTotalsAction $calculateTotals,
    ) {}

    /**
     * Finalize an invoice: generate number, create snapshots, lock it.
     *
     * @throws ImmutableInvoiceException
     * @throws ValidationException
     */
    public function execute(Invoice $invoice, ?string $issuedAt = null): Invoice
    {
        // Validate invoice can be finalized
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Seuls les brouillons peuvent être finalisés.',
            ]);
        }

        if ($invoice->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'La facture doit contenir au moins une ligne.',
            ]);
        }

        $settings = BusinessSettings::getInstance();
        if (!$settings) {
            throw ValidationException::withMessages([
                'settings' => 'Les paramètres de l\'entreprise doivent être configurés avant de finaliser une facture.',
            ]);
        }

        // L'existence de l'enregistrement ne suffit pas : une facture doit
        // identifier son émetteur, et elle se conserve dix ans. Un
        // enregistrement vide produirait un document sans en-tête, sans que
        // rien ne le signale — constaté sur des données créées hors du
        // formulaire (2026-08-29).
        //
        // Le contrôle porte volontairement sur le MINIMUM identifiant — un nom
        // et une adresse — et non sur tous les champs exigés par le formulaire.
        // Refuser sur un code postal manquant bloquerait un utilisateur qui
        // facture aujourd'hui, pour un défaut qui ne rend pas le document
        // anonyme.
        $nom = trim((string) ($settings->company_name ?: $settings->legal_name));
        if ($nom === '' || trim((string) $settings->address) === '') {
            throw ValidationException::withMessages([
                'settings' => 'Le nom et l\'adresse de votre entreprise doivent être renseignés avant de finaliser une facture.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $settings, $issuedAt) {
            // Recalculate totals one last time
            $this->calculateTotals->execute($invoice);
            $invoice->refresh();

            // Determine dates first so the formatter can render {month}/{day} consistently
            $issuedDate = $issuedAt ? now()->parse($issuedAt) : ($invoice->issued_at ?? now());
            $invoice->issued_at = $issuedDate;
            $invoice->finalized_at = now();

            // Generate the invoice/credit note number based on type and custom format settings
            $generated = $this->generateNumber->execute(
                $invoice,
                $issuedDate->year,
                $invoice->type ?? Invoice::TYPE_INVOICE,
            );

            // Create snapshots
            $sellerSnapshot = $settings->toSnapshot();
            $buyerSnapshot = $invoice->client->toSnapshot();

            // FEAT-104 : la mention « Créé avec faktur.lu » dépend du plan, pas
            // des paramètres d'entreprise. Elle ne peut donc pas venir de
            // toSnapshot() et se pose ici, au seul moment qui compte : celui où
            // la facture devient définitive. Sans cela, un changement
            // d'abonnement réécrirait le pied de page de tout l'historique.
            $sellerSnapshot['show_branding'] = $invoice->user?->isFree() ?? true;

            $paymentDays = config('billing.default_payment_days', 30);
            $dueDate = $invoice->due_at ?? $issuedDate->copy()->addDays($paymentDays);

            // Update invoice - use withoutEvents to bypass immutability check
            Invoice::withoutEvents(function () use ($invoice, $generated, $sellerSnapshot, $buyerSnapshot, $issuedDate, $dueDate) {
                $invoice->update([
                    'number' => $generated['number'],
                    'sequence_number' => $generated['sequence_number'],
                    'status' => Invoice::STATUS_FINALIZED,
                    'seller_snapshot' => $sellerSnapshot,
                    'buyer_snapshot' => $buyerSnapshot,
                    'issued_at' => $issuedDate,
                    'due_at' => $dueDate,
                    'finalized_at' => now(),
                ]);
            });

            return $invoice->refresh();
        });
    }
}
