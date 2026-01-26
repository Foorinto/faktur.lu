<x-mail::message>
# {{ $isCreditNote ? 'Avoir' : 'Facture' }} {{ $invoice->number }}

Bonjour,

@if($customMessage)
{{ $customMessage }}

@else
@if($isCreditNote)
Veuillez trouver ci-joint l'avoir N° **{{ $invoice->number }}**.
@else
Veuillez trouver ci-joint la facture N° **{{ $invoice->number }}** pour un montant de **{{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}**.
@endif
@endif

@if(!$isCreditNote)
## Détails de la facture

| | |
|:--|--:|
| **Date d'émission** | {{ $invoice->issued_at?->format('d/m/Y') }} |
| **Date d'échéance** | {{ $invoice->due_at?->format('d/m/Y') }} |
| **Montant HT** | {{ number_format($invoice->total_ht, 2, ',', ' ') }} {{ $invoice->currency }} |
| **TVA** | {{ number_format($invoice->total_vat, 2, ',', ' ') }} {{ $invoice->currency }} |
| **Montant TTC** | {{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }} |

## Informations de paiement

@if(!empty($seller['iban']))
- **IBAN:** `{{ $seller['iban'] }}`
@endif
@if(!empty($seller['bic']))
- **BIC:** {{ $seller['bic'] }}
@endif
- **Référence:** {{ $invoice->number }}
@endif

---

Cordialement,

**{{ $seller['company_name'] ?? $seller['name'] ?? 'L\'équipe' }}**

@if(!empty($seller['email']))
{{ $seller['email'] }}
@endif
@if(!empty($seller['phone']))
| {{ $seller['phone'] }}
@endif

<x-mail::subcopy>
Ce message a été envoyé automatiquement. La facture est jointe à cet email au format PDF.
</x-mail::subcopy>
</x-mail::message>
