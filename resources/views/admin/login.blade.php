<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin - {{ config('marque.nom') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 40px; width: 100%; max-width: 400px; margin: 20px; }
        h1 { font-size: 24px; font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #64748b; text-align: center; margin-bottom: 32px; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; display: none; }
        .error.visible { display: block; }
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
        <p class="subtitle">{{ config('marque.nom') }}</p>

        <div id="error" class="error"></div>

        <form id="loginForm" onsubmit="return handleSubmit(event)">
            <div class="field">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" id="submitBtn">Se connecter</button>
        </form>
    </div>

    <script>
        async function handleSubmit(e) {
            e.preventDefault();

            var btn = document.getElementById('submitBtn');
            var errorDiv = document.getElementById('error');
            btn.disabled = true;
            btn.textContent = 'Connexion...';
            errorDiv.classList.remove('visible');

            try {
                var response = await fetch('{{ $loginUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ $csrfToken }}',
                    },
                    body: JSON.stringify({
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value,
                    }),
                    credentials: 'same-origin',
                });

                var data = await response.json().catch(function() { return null; });

                if (response.ok && data && data.redirect) {
                    window.location.href = data.redirect;
                    return false;
                } else if (data && data.message) {
                    errorDiv.textContent = data.message;
                    errorDiv.classList.add('visible');
                } else {
                    errorDiv.textContent = 'Erreur inattendue. Veuillez réessayer.';
                    errorDiv.classList.add('visible');
                }
            } catch (err) {
                errorDiv.textContent = 'Erreur de connexion. Veuillez réessayer.';
                errorDiv.classList.add('visible');
            }

            btn.disabled = false;
            btn.textContent = 'Se connecter';
            return false;
        }
    </script>
</body>
</html>
