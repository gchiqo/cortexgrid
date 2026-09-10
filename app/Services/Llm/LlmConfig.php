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
    public const OPENAI_COMPATIBLE = ['groq', 'openrouter', 'nvidia', 'cerebras'];

    public const TIERS = ['fast', 'standard', 'max'];

    /** Where an operator goes to create a key for each provider. */
    public const CONSOLE_URLS = [
        'groq' => 'https://console.groq.com/keys',
        'openrouter' => 'https://openrouter.ai/keys',
        'nvidia' => 'https://build.nvidia.com',
        'cerebras' => 'https://cloud.cerebras.ai',
        'anthropic' => 'https://console.anthropic.com/settings/keys',
    ];

    public static function provider(): string
    {
        return (string) Setting::get('llm.provider', config('services.llm.provider', 'groq'));
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
        return self::forProvider(self::provider());
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
                "LLM provider [{$cfg['provider']}] has no API key. Set one in the admin panel under Settings, or in .env."
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
