<x-mail::message>
# {{ __('app.email_admin_new_user_heading') }}

{{ __('app.email_admin_new_user_intro', ['app' => config('app.name')]) }}

@if($user->flagged_for_review)
<x-mail::panel>
{{ __('app.email_admin_new_user_flagged', ['reason' => \App\Services\AbuseProtectionService::describeReason($user->flagged_reason)]) }}
</x-mail::panel>
@endif

**{{ __('app.email_admin_new_user_name') }}** {{ $user->name }}

**{{ __('app.email_admin_new_user_email') }}** {{ $user->email }}

**{{ __('app.email_admin_new_user_date') }}** {{ $user->created_at->format('d/m/Y H:i') }}

---

<x-mail::button :url="$adminUrl">
{{ __('app.email_admin_new_user_cta') }}
</x-mail::button>

{{ __('app.email_regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
