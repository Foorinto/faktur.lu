<x-mail::message>
# Bonjour {{ $user->name }},

{!! $body !!}

<x-mail::button :url="config('app.url')" color="primary">
Accéder à faktur.lu
</x-mail::button>

Cordialement,<br>
L'équipe faktur.lu

<small style="color: #999;">
<a href="{{ $unsubscribeUrl }}" style="color: #999;">Se désinscrire de ces emails</a>
</small>
</x-mail::message>
