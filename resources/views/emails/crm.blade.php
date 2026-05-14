<x-mail::message>
{{ __('app.email_greeting_name', ['name' => $client->contact_name ?: $client->name]) }}

{!! $body !!}

---

{{ __('app.email_regards') }}

**{{ $senderName ?? __('app.email_fallback_team') }}**

@if($senderEmail)
{{ $senderEmail }}
@endif
@if($senderPhone)
| {{ $senderPhone }}
@endif

<x-mail::subcopy>
{{ __('app.email_crm_subcopy') }}
</x-mail::subcopy>
</x-mail::message>
