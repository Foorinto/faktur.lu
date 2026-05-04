<x-mail::message>
Bom dia {{ $client->contact_name ?: $client->name }},

{!! $body !!}

---

Com os melhores cumprimentos,

**{{ $senderName ?? 'A equipa' }}**

@if($senderEmail)
{{ $senderEmail }}
@endif
@if($senderPhone)
| {{ $senderPhone }}
@endif

<x-mail::subcopy>
Este email foi enviado através do faktur.lu. Para qualquer questão, responda diretamente a este email.
</x-mail::subcopy>
</x-mail::message>
