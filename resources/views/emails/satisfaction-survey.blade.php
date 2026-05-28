<x-mail::message>
# {{ $greeting }}

{{ $intro }}

<x-mail::button :url="$surveyUrl" color="primary">
{{ $ctaLabel }}
</x-mail::button>

{{ $regards }}<br>
{{ $signature }}
</x-mail::message>
