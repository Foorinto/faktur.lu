<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin - faktur.lu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 40px; width: 100%; max-width: 400px; margin: 20px; }
        h1 { font-size: 24px; font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #64748b; text-align: center; margin-bottom: 32px; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #9b5de5; box-shadow: 0 0 0 3px rgba(155,93,229,0.1); }
        .field { margin-bottom: 20px; }
        button { width: 100%; padding: 12px; background: #9b5de5; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #8b4dd5; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Administration</h1>
        <p class="subtitle">faktur.lu</p>

        @if($error)
            <div class="error">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ $loginUrl }}">
            <input type="hidden" name="_token" value="{{ $csrfToken }}">

            <div class="field">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
