<x-mail::message>
# @if($level === 1)
{{ __('app.email_reminder_level_1') }}
@elseif($level === 2)
{{ __('app.email_reminder_level_2') }}
@else
{{ __('app.email_reminder_level_3') }}
@endif

{{ $customMessage }}

## {{ __('app.email_invoice_details_heading') }}

| | |
|:--|--:|
| **{{ __('app.email_reminder_number') }}** | {{ $invoice->number }} |
| **{{ __('app.email_invoice_issued_at') }}** | {{ $invoice->issued_at?->format('d/m/Y') }} |
| **{{ __('app.email_invoice_due_at') }}** | {{ $invoice->due_at?->format('d/m/Y') }} |
| **{{ __('app.email_invoice_amount_ttc') }}** | {{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }} |
@if($daysOverdue > 0)
| **{{ __('app.email_reminder_overdue') }}** | {{ $daysOverdue }} {{ $daysOverdue > 1 ? __('app.email_reminder_days') : __('app.email_reminder_day') }} |
@endif

## {{ __('app.email_invoice_payment_info') }}

@if(!empty($seller['iban']))
- **IBAN:** `{{ $seller['iban'] }}`
@endif
@if(!empty($seller['bic']))
- **BIC:** {{ $seller['bic'] }}
@endif
- **{{ __('app.email_invoice_reference') }}:** {{ $invoice->number }}

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
{{ __('app.email_reminder_subcopy') }}
</x-mail::subcopy>
</x-mail::message>
