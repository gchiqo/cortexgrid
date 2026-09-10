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
        'groq' => 'Free and fast, no card required.',
        'openrouter' => 'Widest model choice, including ":free" ids. Free tier is about 50 requests a day, and not every free model supports tool calling.',
        'nvidia' => 'Free credits, including large models.',
        'cerebras' => 'Very fast inference, with a free tier.',
        'anthropic' => 'Paid — the account needs credit.',
    ],

    'get_key' => 'Get a key',
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
