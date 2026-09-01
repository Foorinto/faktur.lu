@php
    $locale = app()->getLocale();
    $supportedLocales = ['fr', 'de', 'en', 'lb', 'pt'];
    $locale = in_array($locale, $supportedLocales) ? $locale : 'fr';

    $messages = [
        'fr' => [
            'title' => 'Page introuvable',
            'subtitle' => 'La page que vous cherchez n\'existe pas ou a été déplacée.',
            'home' => 'Retour à l\'accueil',
            'pricing' => 'Voir les tarifs',
        ],
        'de' => [
            'title' => 'Seite nicht gefunden',
            'subtitle' => 'Die gesuchte Seite existiert nicht oder wurde verschoben.',
            'home' => 'Zurück zur Startseite',
            'pricing' => 'Preise ansehen',
        ],
        'en' => [
            'title' => 'Page not found',
            'subtitle' => 'The page you are looking for doesn\'t exist or has been moved.',
            'home' => 'Back to home',
            'pricing' => 'View pricing',
        ],
        'lb' => [
            'title' => 'Säit net fonnt',
            'subtitle' => 'D\'Säit déi Dir sicht existéiert net oder gouf verréckelt.',
            'home' => 'Zréck op d\'Haaptsäit',
            'pricing' => 'Präisser kucken',
        ],
        'pt' => [
            'title' => 'Página não encontrada',
            'subtitle' => 'A página que procura não existe ou foi movida.',
            'home' => 'Voltar ao início',
            'pricing' => 'Ver preços',
        ],
    ];

    $t = $messages[$locale];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>404 - {{ $t['title'] }} | faktur.lu</title>
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=dosis:600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            color: #1e293b;
            display: flex;
            flex-direction: column;
        }
        .nav {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
        }
        .nav a { display: flex; align-items: center; text-decoration: none; }
        .nav img { height: 32px; width: auto; }
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            max-width: 32rem;
            width: 100%;
            text-align: center;
        }
        .code {
            font-family: 'Dosis', sans-serif;
            font-size: 8rem;
            font-weight: 600;
            line-height: 1;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        p { color: #64748b; margin-bottom: 2rem; line-height: 1.6; }
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-primary {
            background: #6366f1;
            color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,.3);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,.4); }
        .btn-secondary {
            background: #fff;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-secondary:hover { background: #f8fafc; }
        @media (max-width: 480px) {
            .code { font-size: 5rem; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <a href="/{{ $locale }}" aria-label="faktur.lu">
            <img src="/images/logo.png" alt="{{ config('marque.nom') }}" />
        </a>
    </nav>
    <main class="main">
        <div class="card">
            <div class="code">404</div>
            <h1>{{ $t['title'] }}</h1>
            <p>{{ $t['subtitle'] }}</p>
            <div class="actions">
                <a href="/{{ $locale }}" class="btn btn-primary">{{ $t['home'] }}</a>
                <a href="/{{ $locale }}/tarifs" class="btn btn-secondary">{{ $t['pricing'] }}</a>
            </div>
        </div>
    </main>
</body>
</html>
