<x-mail::message>
# Resposta ao seu pedido de suporte

Bom dia {{ $ticket->user->name }},

Recebeu uma resposta relativa ao seu pedido **{{ $ticket->reference }}**:

**Assunto:** {{ $ticket->subject }}

---

{!! nl2br(e($replyContent)) !!}

---

<x-mail::button :url="$ticketUrl">
Ver e responder
</x-mail::button>

Pode consultar o histórico completo e responder diretamente a partir da sua área pessoal.

Com os melhores cumprimentos,<br>
A equipa {{ config('app.name') }}
</x-mail::message>
