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

@if (! empty($freePlanImpact))
{{-- FEAT-105 : les chiffres du compte, pas un argumentaire. Ce bloc
     n'apparaît que si le plan Gratuit changerait réellement quelque chose. --}}
## {{ __('app.email_trial_your_usage_title') }}

{{-- La réassurance vient AVANT les chiffres, à dessein : annoncer un plafond
     sans dire d'abord ce qu'il advient des données laisse imaginer le pire. --}}
{{ __('app.email_trial_usage_reassurance') }}

{{ __('app.email_trial_your_usage_intro') }}

@foreach ($freePlanImpact as $row)
- {{ __('app.email_trial_usage_line', [
      'used' => $row['used'],
      'item' => __('app.quota_alert.types.'.$row['type']),
      'limit' => $row['limit'],
  ]) }}
@endforeach
@endif

## {{ __('app.email_trial_keep_invoicing') }}

{{ __('app.email_trial_choose_plan') }}

**{{ __('app.email_trial_plan_essential') }}** - {{ __('app.email_trial_price_essential', ['price' => $essentiel?->price_monthly_euros ?? 5]) }}
- {{ __('app.email_trial_essential_quota', [
    'clients' => $essentiel?->getLimit('max_clients') ?? 100,
    'invoices' => $essentiel?->getLimit('max_invoices_per_month') ?? 50,
]) }}
- {{ __('app.email_trial_essential_tagline') }}

**{{ __('app.email_trial_plan_pro') }}** - {{ __('app.email_trial_price_pro', ['price' => $pro?->price_monthly_euros ?? 15]) }}
- {{ __('app.email_trial_pro_features') }}
- {{ __('app.email_trial_pro_tagline') }}

<x-mail::button :url="$subscriptionUrl" color="primary">
{{ __('app.email_trial_cta_choose') }}
</x-mail::button>

{{ __('app.email_trial_questions') }}

{{ __('app.email_regards') }}<br>
{{ __('app.email_team_signature') }}
</x-mail::message>
