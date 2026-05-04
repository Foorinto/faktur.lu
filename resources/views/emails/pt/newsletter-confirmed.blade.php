<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscrição confirmada - faktur.lu</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #334155; }
        .card { background: white; border-radius: 16px; padding: 48px; max-width: 480px; text-align: center; border: 1px solid #e2e8f0; }
        h1 { font-size: 24px; margin-bottom: 16px; color: #10b981; }
        p { color: #64748b; line-height: 1.6; }
        a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Subscrição confirmada!</h1>
        <p>O seu endereço de email foi confirmado. A partir de agora vai receber as nossas dicas sobre faturação e fiscalidade no Luxemburgo.</p>
        <p><a href="{{ config('app.url') }}">Voltar ao faktur.lu</a></p>
    </div>
</body>
</html>
