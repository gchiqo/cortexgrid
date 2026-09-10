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
        'model' => env('GROQ_MODEL', 'qwen/qwen3.8-27b'),
        'base_url' => 'https://api.groq.com/openai/v1',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    | Which backend answers questions. 'anthropic' uses Claude directly;
    | every other value is an OpenAI-compatible endpoint from the list below,
    | so switching provider is a .env change rather than a code change.
    */
    'llm' => [
        'provider' => env('LLM_PROVIDER', 'groq'),

        'providers' => [
            'groq' => [
                'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
                'key' => env('GROQ_API_KEY'),
                'model' => env('GROQ_CHAT_MODEL', 'openai/gpt-oss-120b'),
                'tiers' => [
                    'fast' => env('GROQ_TIER_FAST', 'qwen/qwen3.8-27b'),
                    'standard' => env('GROQ_TIER_STANDARD', 'openai/gpt-oss-120b'),
                    'max' => env('GROQ_TIER_MAX', 'openai/gpt-oss-120b'),
                ],
            ],

            /*
            | Gemini also speaks the OpenAI chat format, so it needs no driver
            | of its own. Reuses GEMINI_API_KEY, which the app already has for
            | embeddings — nothing extra to register.
            |
            | The Pro models are quota-locked on the free tier (429), and the
            | "latest" aliases return 503 under load, so the flash models are
            | the ones that actually answer.
            */
            'gemini' => [
                'base_url' => env('GEMINI_OPENAI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
                // A second key (a different Google project) keeps chat off the
                // same free quota as embeddings. Falls back to the embeddings
                // key when unset, so one key still works for both.
                'key' => env('GEMINI_CHAT_API_KEY') ?: env('GEMINI_API_KEY'),
                'model' => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash'),
                'tiers' => [
                    'fast' => env('GEMINI_TIER_FAST', 'gemini-2.5-flash'),
                    'standard' => env('GEMINI_TIER_STANDARD', 'gemini-2.5-flash'),
                    'max' => env('GEMINI_TIER_MAX', 'gemini-3-flash-preview'),
                ],
            ],

            'openrouter' => [
                'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
                'key' => env('OPENROUTER_API_KEY'),
                'model' => env('OPENROUTER_MODEL', 'nex-agi/nex-n2.5-pro:free'),
                // OpenRouter attributes usage to your app when these are sent.
                'headers' => array_filter([
                    'HTTP-Referer' => env('APP_URL'),
                    'X-Title' => env('APP_NAME'),
                ]),
                'tiers' => [
                    // Availability of ":free" ids swings with OpenRouter's shared
                    // pool, and the alternatives each fail one requirement:
                    // Gemma is frequently 429, the Nemotron models write their
                    // reasoning into the answer body, and nex-n2.5-mini will
                    // not call tools. nex-n2.5-pro does all three, so it is
                    // used across every tier.
                    'fast' => env('OPENROUTER_TIER_FAST', 'nex-agi/nex-n2.5-pro:free'),
                    'standard' => env('OPENROUTER_TIER_STANDARD', 'nex-agi/nex-n2.5-pro:free'),
                    'max' => env('OPENROUTER_TIER_MAX', 'nex-agi/nex-n2.5-pro:free'),
                ],
            ],

            'nvidia' => [
                'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
                'key' => env('NVIDIA_API_KEY'),
                'model' => env('NVIDIA_MODEL', 'nvidia/nemotron-3-super-120b-a12b'),
                'tiers' => [
                    // Most ids this account lists never answer: gpt-oss-20b and
                    // both deepseek-v4 builds time out with zero bytes. Nemotron
                    // is the one that reliably responds, so every tier uses it.
                    'fast' => env('NVIDIA_TIER_FAST', 'nvidia/nemotron-3-super-120b-a12b'),
                    'standard' => env('NVIDIA_TIER_STANDARD', 'nvidia/nemotron-3-super-120b-a12b'),
                    'max' => env('NVIDIA_TIER_MAX', 'nvidia/nemotron-3-super-120b-a12b'),
                ],
            ],

            'cerebras' => [
                'base_url' => env('CEREBRAS_BASE_URL', 'https://api.cerebras.ai/v1'),
                'key' => env('CEREBRAS_API_KEY'),
                'model' => env('CEREBRAS_MODEL', 'gpt-oss-120b'),
                'tiers' => [
                    'fast' => env('CEREBRAS_TIER_FAST', 'qwen-3.8-27b'),
                    'standard' => env('CEREBRAS_TIER_STANDARD', 'gpt-oss-120b'),
                    'max' => env('CEREBRAS_TIER_MAX', 'gpt-oss-120b'),
                ],
            ],
        ],
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
