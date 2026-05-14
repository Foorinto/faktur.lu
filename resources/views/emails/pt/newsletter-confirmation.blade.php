<x-mail::message>
# {{ __('app.email_newsletter_confirm_heading') }}

{{ __('app.email_newsletter_confirm_thanks') }}

{{ __('app.email_newsletter_confirm_intro') }}

<x-mail::button :url="$confirmUrl" color="primary">
{{ __('app.email_newsletter_confirm_cta') }}
</x-mail::button>

{{ __('app.email_newsletter_confirm_ignore') }}

{{ __('app.email_regards') }}<br>
{{ __('app.email_team_signature') }}
</x-mail::message>
