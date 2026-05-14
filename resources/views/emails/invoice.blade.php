<x-mail::message>
# {{ $isCreditNote ? __('app.email_invoice_heading_credit_note') : __('app.email_invoice_heading_invoice') }} {{ $invoice->number }}

{{ __('app.email_greeting_generic') }}

@if($customMessage)
{{ $customMessage }}

@else
@if($isCreditNote)
{!! __('app.email_invoice_attached_credit_note', ['number' => $invoice->number]) !!}
@else
{!! __('app.email_invoice_attached_invoice', ['number' => $invoice->number, 'amount' => number_format($invoice->total_ttc, 2, ',', ' ').' '.$invoice->currency]) !!}
@endif
@endif

@if(!$isCreditNote)
## {{ __('app.email_invoice_details_heading') }}

| | |
|:--|--:|
| **{{ __('app.email_invoice_issued_at') }}** | {{ $invoice->issued_at?->format('d/m/Y') }} |
| **{{ __('app.email_invoice_due_at') }}** | {{ $invoice->due_at?->format('d/m/Y') }} |
| **{{ __('app.email_invoice_amount_ht') }}** | {{ number_format($invoice->total_ht, 2, ',', ' ') }} {{ $invoice->currency }} |
| **{{ __('app.email_invoice_vat') }}** | {{ number_format($invoice->total_vat, 2, ',', ' ') }} {{ $invoice->currency }} |
| **{{ __('app.email_invoice_amount_ttc') }}** | {{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }} |

## {{ __('app.email_invoice_payment_info') }}

@if(!empty($seller['iban']))
- **IBAN:** `{{ $seller['iban'] }}`
@endif
@if(!empty($seller['bic']))
- **BIC:** {{ $seller['bic'] }}
@endif
- **{{ __('app.email_invoice_reference') }}:** {{ $invoice->number }}
@endif

---

{{ __('app.email_regards') }}

**{{ $seller['company_name'] ?? $seller['name'] ?? __('app.email_fallback_team') }}**

@if(!empty($seller['email']))
{{ $seller['email'] }}
@endif
@if(!empty($seller['phone']))
| {{ $seller['phone'] }}
@endif

<x-mail::subcopy>
{{ __('app.email_invoice_subcopy') }}
</x-mail::subcopy>
</x-mail::message>
