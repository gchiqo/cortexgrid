<?php

namespace App\Providers;

use App\Services\Llm\LlmConfig;
use App\Services\Llm\TextGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Which backend answers questions is a runtime decision: the admin
        // panel overrides .env, so this is bound per-resolution, not shared.
        $this->app->bind(TextGenerator::class, fn () => LlmConfig::make());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
