<x-mail::message>
# {{ __('app.email_greeting_name', ['name' => $user->name]) }}

{!! __('app.email_trial_ending_intro', [
    'days' => $daysRemaining,
    'unit' => $daysRemaining > 1 ? __('app.email_reminder_days') : __('app.email_reminder_day'),
]) !!}

{{ __('app.email_trial_ending_access_intro') }}
- {{ __('app.email_trial_feature_unlimited') }}
- {{ __('app.email_trial_feature_faia') }}
- {{ __('app.email_trial_feature_archive') }}
- {{ __('app.email_trial_feature_reminders') }}

## {{ __('app.email_trial_keep_invoicing') }}

{{ __('app.email_trial_choose_plan') }}

**{{ __('app.email_trial_plan_essential') }}** - {{ __('app.email_trial_price_essential') }}
- {{ __('app.email_trial_essential_quota') }}
- {{ __('app.email_trial_essential_tagline') }}

**{{ __('app.email_trial_plan_pro') }}** - {{ __('app.email_trial_price_pro') }}
- {{ __('app.email_trial_pro_features') }}
- {{ __('app.email_trial_pro_tagline') }}

<x-mail::button :url="$subscriptionUrl" color="primary">
{{ __('app.email_trial_cta_choose') }}
</x-mail::button>

{{ __('app.email_trial_questions') }}

{{ __('app.email_regards') }}<br>
{{ __('app.email_team_signature') }}
</x-mail::message>
