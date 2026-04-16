<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'pro_monthly' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
        'pro_yearly' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
        'essentiel_monthly' => env('STRIPE_ESSENTIEL_MONTHLY_PRICE_ID'),
        'essentiel_yearly' => env('STRIPE_ESSENTIEL_YEARLY_PRICE_ID'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'newsletter_list_id' => env('BREVO_NEWSLETTER_LIST_ID', 3),
        'doi_template_id' => env('BREVO_DOI_TEMPLATE_ID', 1),
    ],

];
