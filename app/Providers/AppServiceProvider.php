<?php

namespace App\Providers;

use App\Services\Anthropic;
use App\Services\Llm\OpenAiCompatible;
use App\Services\Llm\TextGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolve the answer-generation backend from config: 'anthropic' talks
        // to Claude directly, anything else is an OpenAI-compatible endpoint.
        $this->app->singleton(TextGenerator::class, function () {
            $provider = (string) config('services.llm.provider', 'groq');

            if ($provider === 'anthropic') {
                return new Anthropic;
            }

            $cfg = config("services.llm.providers.{$provider}");

            if (! is_array($cfg) || blank($cfg['key'] ?? null)) {
                throw new \RuntimeException(
                    "LLM provider [{$provider}] is not configured. Set LLM_PROVIDER to a provider with an API key in config/services.php."
                );
            }

            return new OpenAiCompatible(
                baseUrl: rtrim((string) $cfg['base_url'], '/'),
                key: (string) $cfg['key'],
                model: (string) $cfg['model'],
                headers: $cfg['headers'] ?? [],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
