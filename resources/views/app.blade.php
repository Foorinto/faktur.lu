<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Bing Webmaster Tools site verification --}}
        <meta name="msvalidate.01" content="13148CA3A8D92D8A7B0AAD35CAEE00D4" />

        {{-- Canonical for public multilingual pages.
             Hreflang alternates are only emitted for routes whose URL slug is identical across
             all 5 locales (homepage and selected slug-stable routes). Pricing, features, blog
             posts etc. have language-specific slugs (/fr/tarifs vs /de/preise) — emitting
             naive hreflang on those would point to non-existent URLs. Those routes get a
             canonical only; their hreflang relations are owned by the per-page SeoHead component. --}}
        @php
            $supportedLocales = ['fr', 'de', 'en', 'lb', 'pt'];
            $segments = request()->segments();
            $firstSegment = $segments[0] ?? null;
            $isLocalizedPublicRoute = in_array($firstSegment, $supportedLocales, true);

            // Routes whose slug after the locale is identical across all 5 languages.
            // Keep this list in sync with routes/web.php — only add a slug here if it exists
            // verbatim in every supported locale. Most marketing slugs (a-propos/ueber-uns,
            // partenaires/partner, etc.) DIFFER per locale, so they must NOT be added here.
            $slugStableRoutes = [
                '',         // homepage: /fr, /de, /en, /lb, /pt
                'contact',  // declared without per-locale variants
                'cookies',  // declared without per-locale variants
            ];

            if ($isLocalizedPublicRoute) {
                $pathWithoutLocale = '/' . implode('/', array_slice($segments, 1));
                $pathWithoutLocale = rtrim($pathWithoutLocale, '/');
                $slugAfterLocale = ltrim($pathWithoutLocale, '/');
                $base = rtrim(config('app.url') ?: 'https://faktur.lu', '/');
                $canSafelyEmitHreflang = in_array($slugAfterLocale, $slugStableRoutes, true);
            }
        @endphp
        @if($isLocalizedPublicRoute)
            <link rel="canonical" href="{{ $base }}/{{ $firstSegment }}{{ $pathWithoutLocale }}" />
            @if($canSafelyEmitHreflang)
                @foreach($supportedLocales as $altLocale)
                    <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $base }}/{{ $altLocale }}{{ $pathWithoutLocale }}" />
                @endforeach
                <link rel="alternate" hreflang="x-default" href="{{ $base }}/fr{{ $pathWithoutLocale }}" />
            @endif
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicons -->
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="faktur.lu" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Fonts : preconnect + preload de la CSS pour la rendre non-bloquante.
             Le pattern media=print + onload basculera en stylesheet une fois charge.
             Lighthouse: economise ~600ms sur First Contentful Paint mobile. -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=inter:400,500,600&family=dosis:600&display=swap">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=dosis:600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=dosis:600&display=swap" rel="stylesheet"></noscript>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        {{-- Server-side JSON-LD (structured data). Rendered here so Googlebot sees it in
             the initial HTML without executing JS. Only present on pages that pass a
             `structuredData` prop (currently the homepage). --}}
        @if(!empty($page['props']['structuredData']))
            @foreach($page['props']['structuredData'] as $schema)
                <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
            @endforeach
        @endif

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
