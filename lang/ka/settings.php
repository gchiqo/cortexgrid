<?php

return [
    'title' => 'პარამეტრები',
    'heading' => 'მოდელის პროვაიდერი',
    'subtitle' => 'აირჩიე რომელი მოდელი პასუხობს კითხვებზე და შეიყვანე მისი API გასაღები. პანელში შენახული მნიშვნელობა ჭარბობს .env-ს.',

    'provider' => [
        'groq' => 'Groq',
        'openrouter' => 'OpenRouter',
        'nvidia' => 'NVIDIA NIM',
        'cerebras' => 'Cerebras',
        'anthropic' => 'Anthropic (Claude)',
    ],
    'hint' => [
        'groq' => 'უფასო და სწრაფი. გასაღები: console.groq.com/keys',
        'openrouter' => 'ყველაზე მეტი მოდელი, მათ შორის „:free". უფასო ლიმიტი ~50 მოთხოვნა/დღეში. გასაღები: openrouter.ai/keys',
        'nvidia' => 'უფასო კრედიტები. გასაღები: build.nvidia.com',
        'cerebras' => 'ძალიან სწრაფი ინფერენსი, უფასო ლიმიტით. გასაღები: cloud.cerebras.ai',
        'anthropic' => 'ფასიანი. საჭიროა ბალანსი: console.anthropic.com',
    ],

    'api_key' => 'API გასაღები',
    'paste_key' => 'ჩასვი გასაღები…',
    'leave_blank' => 'ცარიელი = უცვლელი',
    'clear_key' => 'წაშლა',
    'from_env' => 'აღებულია .env-იდან. პანელში შეყვანილი გასაღები ჩაანაცვლებს მას.',
    'key_set' => 'გასაღები არის',
    'no_key_badge' => 'გასაღების გარეშე',
    'no_key' => 'ამ პროვაიდერს გასაღები არ აქვს.',
    'base_url' => 'Base URL',
    'default_model' => 'ნაგულისხმევი მოდელი',
    'tier' => 'დონე',

    'test' => 'შემოწმება',
    'testing' => 'მოწმდება…',
    'test_ok' => '✓ მუშაობს',
    'test_fail' => '✗ ვერ მოხერხდა',
    'models_found' => ':count მოდელი',
];
