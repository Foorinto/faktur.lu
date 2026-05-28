<x-mail::message>
# Nouvelle réponse au sondage de satisfaction

**Client :** {{ $user?->name ?? '—' }}

**Email :** {{ $user?->email ?? '—' }}

**Score NPS :** {{ $survey->nps_score }}/10 ({{ $category }})

**Date :** {{ $survey->completed_at?->format('d/m/Y H:i') ?? '—' }}

@if($survey->comment)
---

**Commentaire :**

{!! nl2br(e($survey->comment)) !!}
@endif

<x-mail::button :url="$adminUrl">
Voir toutes les réponses
</x-mail::button>

{{ __('app.email_regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
