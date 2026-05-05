<!DOCTYPE html>
@php
    // Locale meta map for og:locale (rendered server-side so scrapers/AI see proper meta)
    $localeMap = ['fr' => 'fr_LU', 'de' => 'de_LU', 'en' => 'en_GB', 'lb' => 'lb_LU', 'pt' => 'pt_PT'];
    $currentLocale = app()->getLocale();
    $ogLocale = $localeMap[$currentLocale] ?? 'fr_LU';
    $ogTitle = __('app.landing.page_title');
    $ogDescription = __('app.landing.meta_description');
    $appUrl = config('app.url', 'https://faktur.lu');
    $currentUrl = url()->current();
    $ogImage = $appUrl . '/images/og-default.png';
@endphp
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $ogTitle }}</title>

        {{-- Server-side OG fallback : indispensable car Inertia rend cote client.
             Sans ce bloc, les scrapers (Facebook, LinkedIn, Twitter, opengraph.xyz)
             et les IA (ChatGPT, Claude, Perplexity) voient un <head> vide. --}}
        <meta name="description" content="{{ $ogDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ $currentUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $ogTitle }}">
        <meta property="og:locale" content="{{ $ogLocale }}">
        @foreach ($localeMap as $code => $value)
            @if ($code !== $currentLocale)
        <meta property="og:locale:alternate" content="{{ $value }}">
            @endif
        @endforeach
        <meta property="og:site_name" content="faktur.lu">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
        <meta name="twitter:image:alt" content="{{ $ogTitle }}">
        <meta name="twitter:site" content="@fakturlu">

        {{-- AI/LLM crawlers : pointer vers la version markdown structuree --}}
        <link rel="alternate" type="text/markdown" title="LLM-friendly version" href="/llms.txt">

        <!-- Favicons -->
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="faktur.lu" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=dosis:600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        {{-- Matomo Analytics - Self-hosted, GDPR compliant, cookieless --}}
        @if(config('analytics.enabled') && config('analytics.matomo.url'))
        <script>
            var _paq = window._paq = window._paq || [];
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
                var u="{{ config('analytics.matomo.url') }}";
                _paq.push(['setTrackerUrl', u+'matomo.php']);
                _paq.push(['setSiteId', '{{ config('analytics.matomo.site_id') }}']);
                var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
                g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
            })();
        </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
