<x-mail::message>
# Nouvelle demande de support

Une nouvelle demande de support a été soumise.

**Référence:** {{ $ticket->reference }}

**De:** {{ $ticket->user->name }} ({{ $ticket->user->email }})

**Catégorie:** {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}

**Sujet:** {{ $ticket->subject }}

---

**Message:**

{{ $messageContent }}

---

<x-mail::button :url="$adminUrl">
Voir le ticket
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
