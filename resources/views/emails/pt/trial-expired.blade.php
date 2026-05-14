<x-mail::message>
# {{ __('app.email_greeting_name', ['name' => $user->name]) }}

{{ __('app.email_trial_expired_intro') }}

## {{ __('app.email_trial_expired_readonly_h') }}

{{ __('app.email_trial_expired_login_intro') }}
- {{ __('app.email_trial_expired_view_inv') }}
- {{ __('app.email_trial_expired_download') }}
- {{ __('app.email_trial_expired_access_data') }}

{{ __('app.email_trial_expired_no_create') }}

## {{ __('app.email_trial_expired_reactivate_h') }}

{{ __('app.email_trial_expired_reactivate_p') }}

**{{ __('app.email_trial_plan_essential') }}** - {{ __('app.email_trial_price_essential') }}
- {{ __('app.email_trial_essential_quota') }}

**{{ __('app.email_trial_plan_pro') }}** - {{ __('app.email_trial_price_pro') }}
- {{ __('app.email_trial_pro_features') }}

<x-mail::button :url="$subscriptionUrl" color="primary">
{{ __('app.email_trial_expired_cta') }}
</x-mail::button>

{{ __('app.email_trial_expired_data_safe') }}

{{ __('app.email_regards') }}<br>
{{ __('app.email_team_signature') }}
</x-mail::message>
