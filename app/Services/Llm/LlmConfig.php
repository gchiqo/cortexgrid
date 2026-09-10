<?php

namespace App\Services\Llm;

use App\Models\Setting;

/**
 * Resolves which model backend is active.
 *
 * Panel settings win; anything the admin has not set falls back to
 * config/services.php (i.e. .env), so an install driven purely by .env keeps
 * working and the panel is an override layer rather than a replacement.
 */
class LlmConfig
{
    /** Providers that speak the OpenAI /chat/completions format. */
    public const OPENAI_COMPATIBLE = ['groq', 'gemini', 'cerebras', 'openrouter', 'nvidia'];

    public const TIERS = ['fast', 'standard', 'max'];

    /** Where an operator goes to create a key for each provider. */
    public const CONSOLE_URLS = [
        'groq' => 'https://console.groq.com/keys',
        'gemini' => 'https://aistudio.google.com/apikey',
        'openrouter' => 'https://openrouter.ai/keys',
        'nvidia' => 'https://build.nvidia.com',
        'cerebras' => 'https://cloud.cerebras.ai',
        'anthropic' => 'https://console.anthropic.com/settings/keys',
    ];

    /** The provider the operator selected, whether or not it is usable. */
    public static function provider(): string
    {
        return (string) Setting::get('llm.provider', config('services.llm.provider', 'groq'));
    }

    /**
     * The provider actually used for a request.
     *
     * Normally the selected one. If it has no key — a fresh install, or a key
     * cleared from the panel — fall back to any provider that does, so the app
     * keeps answering instead of failing outright. Falls back to the selection
     * when nothing is configured, so the error names the provider the operator
     * actually chose.
     */
    public static function resolvedProvider(): string
    {
        $selected = self::provider();

        if (self::isConfigured($selected)) {
            return $selected;
        }

        foreach (self::providers() as $candidate) {
            if (self::isConfigured($candidate)) {
                return $candidate;
            }
        }

        return $selected;
    }

    /** True when the selected provider is unusable and another is standing in. */
    public static function isFallingBack(): bool
    {
        return self::resolvedProvider() !== self::provider();
    }

    /** @return list<string> every provider the panel can offer */
    public static function providers(): array
    {
        return [...self::OPENAI_COMPATIBLE, 'anthropic'];
    }

    /**
     * The active provider's resolved settings.
     *
     * @return array{provider:string,base_url:string,key:string,model:string,tiers:array<string,string>,headers:array<string,string>}
     */
    public static function active(): array
    {
        return self::forProvider(self::resolvedProvider());
    }

    /**
     * @return array{provider:string,base_url:string,key:string,model:string,tiers:array<string,string>,headers:array<string,string>}
     */
    public static function forProvider(string $provider): array
    {
        $base = $provider === 'anthropic'
            ? (array) config('services.anthropic', [])
            : (array) config("services.llm.providers.{$provider}", []);

        $tiers = [];
        foreach (self::TIERS as $tier) {
            $tiers[$tier] = (string) Setting::get(
                "llm.{$provider}.tier.{$tier}",
                $base['tiers'][$tier] ?? ($base['model'] ?? '')
            );
        }

        return [
            'provider' => $provider,
            'base_url' => (string) Setting::get("llm.{$provider}.base_url", $base['base_url'] ?? ''),
            'key' => (string) Setting::get("llm.{$provider}.key", $base['key'] ?? ''),
            'model' => (string) Setting::get("llm.{$provider}.model", $base['model'] ?? ''),
            'tiers' => $tiers,
            'headers' => (array) ($base['headers'] ?? []),
        ];
    }

    /** Is the active provider usable (has a key)? */
    public static function isConfigured(?string $provider = null): bool
    {
        return filled(self::forProvider($provider ?? self::provider())['key']);
    }

    /** Build the driver for the active provider. */
    public static function make(): TextGenerator
    {
        $cfg = self::active();

        if ($cfg['provider'] === 'anthropic') {
            return new \App\Services\Anthropic($cfg['key'], $cfg['model']);
        }

        if (blank($cfg['key'])) {
            throw new \RuntimeException(
                "No model provider is configured. Add an API key under Settings, or set one in .env."
            );
        }

        return new OpenAiCompatible(
            baseUrl: rtrim($cfg['base_url'], '/'),
            key: $cfg['key'],
            model: $cfg['model'],
            headers: $cfg['headers'],
        );
    }
}
