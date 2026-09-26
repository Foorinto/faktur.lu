<x-mail::message>
# {{ __('app.email_admin_flagged_first_email_heading') }}

{{ __('app.email_admin_flagged_first_email_intro') }}

**{{ __('app.email_admin_new_user_name') }}** {{ $user->name }}

**{{ __('app.email_admin_new_user_email') }}** {{ $user->email }}

**{{ __('app.email_admin_flagged_company') }}** {{ $companyName ?? '-' }}

**{{ __('app.email_admin_flagged_reason') }}** {{ $reason }}

**{{ __('app.email_admin_flagged_invoice') }}** {{ $invoice->number ?? '#'.$invoice->id }}

**{{ __('app.email_admin_flagged_recipient') }}** {{ $recipient }}

**{{ __('app.email_admin_flagged_sent_today') }}** {{ $sentToday }}

---

<x-mail::button :url="$adminUrl">
{{ __('app.email_admin_flagged_cta') }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
