<x-mail::message>
# Nouvelle inscription

Un nouvel utilisateur s'est inscrit sur {{ config('app.name') }}.

**Nom:** {{ $user->name }}

**Email:** {{ $user->email }}

**Date:** {{ $user->created_at->format('d/m/Y à H:i') }}

---

<x-mail::button :url="$adminUrl">
Voir les utilisateurs
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
