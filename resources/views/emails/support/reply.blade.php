<x-mail::message>
# Réponse à votre demande de support

Bonjour {{ $ticket->user->name }},

Vous avez reçu une réponse concernant votre demande **{{ $ticket->reference }}** :

**Sujet :** {{ $ticket->subject }}

---

{!! nl2br(e($replyContent)) !!}

---

<x-mail::button :url="$ticketUrl">
Voir et répondre
</x-mail::button>

Vous pouvez consulter l'historique complet et répondre directement depuis votre espace.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
