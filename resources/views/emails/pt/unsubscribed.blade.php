<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.email_unsubscribed_title') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #334155; }
        .card { background: white; border-radius: 16px; padding: 48px; max-width: 480px; text-align: center; border: 1px solid #e2e8f0; }
        h1 { font-size: 24px; margin-bottom: 16px; }
        p { color: #64748b; line-height: 1.6; }
        a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('app.email_unsubscribed_heading') }}</h1>
        <p>{{ __('app.email_unsubscribed_text1') }}</p>
        <p>{{ __('app.email_unsubscribed_text2') }}</p>
        <p><a href="{{ config('app.url') }}">{{ __('app.email_newsletter_back_link') }}</a></p>
    </div>
</body>
</html>
