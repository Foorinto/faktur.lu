<x-mail::message>
# {{ __('app.email_greeting_name', ['name' => $user->name]) }}

{!! $body !!}

<x-mail::button :url="config('app.url')" color="primary">
{{ __('app.email_drip_cta_access') }}
</x-mail::button>

{{ __('app.email_regards') }}<br>
{{ __('app.email_team_signature') }}

<small style="color: #999;">
<a href="{{ $unsubscribeUrl }}" style="color: #999;">{{ __('app.email_drip_unsubscribe') }}</a>
</small>
</x-mail::message>
