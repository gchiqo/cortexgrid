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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        // model_tier -> model id mapping used by AiConfig
        'tiers' => [
            'fast' => 'claude-haiku-4-5',
            'standard' => 'claude-sonnet-4-6',
            'max' => 'claude-opus-4-8',
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'embedding_dim' => (int) env('EMBEDDING_DIM', 768),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => 'https://api.groq.com/openai/v1',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'flitt' => [
        'merchant_id' => env('FLITT_MERCHANT_ID', '1549901'),
        'secret_key' => env('FLITT_SECRET_KEY', 'test'),
        'checkout_url' => 'https://pay.flitt.com/api/checkout/url',
        'status_url' => 'https://pay.flitt.com/api/status/order_id',
        'allowed_ips' => ['54.154.216.60', '3.75.125.89'],
        // Credit packs (1 credit ≈ 1 token).
        'packs' => [
            ['gel' => 10, 'credits' => 1000000],
            ['gel' => 25, 'credits' => 3000000],
            ['gel' => 50, 'credits' => 7000000],
        ],
    ],

];
