<x-mail::message>
Bonjour {{ $client->contact_name ?: $client->name }},

{!! $body !!}

---

Cordialement,

**{{ $senderName ?? 'L\'équipe' }}**

@if($senderEmail)
{{ $senderEmail }}
@endif
@if($senderPhone)
| {{ $senderPhone }}
@endif

<x-mail::subcopy>
Cet email a été envoyé via faktur.lu. Pour toute question, répondez directement à cet email.
</x-mail::subcopy>
</x-mail::message>
