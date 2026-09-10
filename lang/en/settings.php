<?php

return [
    'title' => 'Settings',
    'heading' => 'Model provider',
    'subtitle' => 'Choose which model answers questions and enter its API key. A value saved here overrides .env.',

    'provider' => [
        'groq' => 'Groq',
        'openrouter' => 'OpenRouter',
        'nvidia' => 'NVIDIA NIM',
        'cerebras' => 'Cerebras',
        'anthropic' => 'Anthropic (Claude)',
    ],
    'hint' => [
        'groq' => 'Free and fast. Key: console.groq.com/keys',
        'openrouter' => 'Widest model choice, including ":free" ids. Free tier is about 50 requests a day. Key: openrouter.ai/keys',
        'nvidia' => 'Free credits. Key: build.nvidia.com',
        'cerebras' => 'Very fast inference with a free tier. Key: cloud.cerebras.ai',
        'anthropic' => 'Paid, needs credit on the account: console.anthropic.com',
    ],

    'api_key' => 'API key',
    'paste_key' => 'Paste a key…',
    'leave_blank' => 'blank = unchanged',
    'clear_key' => 'Clear',
    'from_env' => 'Coming from .env. A key entered here will take over.',
    'key_set' => 'key set',
    'no_key_badge' => 'no key',
    'no_key' => 'This provider has no API key.',
    'base_url' => 'Base URL',
    'default_model' => 'Default model',
    'tier' => 'Tier',

    'test' => 'Test',
    'testing' => 'Testing…',
    'test_ok' => '✓ works',
    'test_fail' => '✗ failed',
    'models_found' => ':count models',
];
