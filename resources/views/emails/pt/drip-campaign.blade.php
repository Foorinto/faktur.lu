<x-mail::message>
# Bom dia {{ $user->name }},

{!! $body !!}

<x-mail::button :url="config('app.url')" color="primary">
Aceder ao faktur.lu
</x-mail::button>

Com os melhores cumprimentos,<br>
A equipa faktur.lu

<small style="color: #999;">
<a href="{{ $unsubscribeUrl }}" style="color: #999;">Cancelar a subscrição destes emails</a>
</small>
</x-mail::message>
