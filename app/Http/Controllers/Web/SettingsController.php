<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Llm\LlmConfig;
use App\Services\Llm\OpenAiCompatible;
use App\Services\Anthropic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Platform settings: which model backend answers questions, and its keys.
 * Admin-only — these are the operator's credentials, not a tenant's.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        $providers = [];
        foreach (LlmConfig::providers() as $name) {
            $cfg = LlmConfig::forProvider($name);
            $providers[$name] = [
                'base_url' => $cfg['base_url'],
                'model' => $cfg['model'],
                'tiers' => $cfg['tiers'],
                'has_key' => filled($cfg['key']),
                // never send the key back to the browser — only a hint that it exists
                'key_hint' => filled($cfg['key']) ? '••••'.substr($cfg['key'], -4) : '',
                'from_env' => filled($cfg['key']) && blank(Setting::get("llm.{$name}.key")),
            ];
        }

        return view('settings', [
            'active' => LlmConfig::provider(),
            'providers' => $providers,
            'tiers' => LlmConfig::TIERS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:'.implode(',', LlmConfig::providers())],
            'keys' => ['array'],
            'keys.*' => ['nullable', 'string', 'max:400'],
            'base_urls' => ['array'],
            'base_urls.*' => ['nullable', 'string', 'max:200'],
            'models' => ['array'],
            'tiers' => ['array'],
        ]);

        $values = ['llm.provider' => $data['provider']];

        foreach (LlmConfig::providers() as $name) {
            // An empty key field means "leave what's stored alone", so a masked
            // form never wipes a working credential on save.
            $key = $data['keys'][$name] ?? null;
            if (filled($key)) {
                $values["llm.{$name}.key"] = trim($key);
            }

            $values["llm.{$name}.base_url"] = $data['base_urls'][$name] ?? null;
            $values["llm.{$name}.model"] = $data['models'][$name] ?? null;

            foreach (LlmConfig::TIERS as $tier) {
                $values["llm.{$name}.tier.{$tier}"] = $data['tiers'][$name][$tier] ?? null;
            }
        }

        Setting::putMany($values);

        return back()->with('status', __('messages.settings_saved'));
    }

    /** Clear a stored key so the .env value (if any) takes over again. */
    public function forgetKey(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, LlmConfig::providers(), true), 404);

        Setting::putMany(["llm.{$provider}.key" => null]);

        return back()->with('status', __('messages.settings_key_cleared'));
    }

    /**
     * Live check: can we reach the provider with the stored credentials, and
     * which models does it offer? Used by the "Test" button.
     */
    public function test(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, LlmConfig::providers(), true), 404);

        $cfg = LlmConfig::forProvider($provider);

        if (blank($cfg['key'])) {
            return response()->json(['ok' => false, 'error' => __('settings.no_key')], 200);
        }

        $t0 = microtime(true);

        try {
            $generator = $provider === 'anthropic'
                ? new Anthropic($cfg['key'], $cfg['model'])
                : new OpenAiCompatible(rtrim($cfg['base_url'], '/'), $cfg['key'], $cfg['model'], $cfg['headers']);

            // Reasoning models spend tokens thinking before they emit content,
            // so a tiny budget can come back blank even on a healthy provider.
            $res = $generator->chat('Reply with the single word: ok', [
                ['role' => 'user', 'content' => 'ping'],
            ], null, 256);

            return response()->json([
                'ok' => true,
                'ms' => (int) round((microtime(true) - $t0) * 1000),
                'reply' => trim(mb_substr($res['text'], 0, 60)),
                'models' => $this->listModels($provider, $cfg),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $this->readableError($e->getMessage()),
            ]);
        }
    }

    /** Providers report failures as multi-line JSON; show one useful line. */
    private function readableError(string $raw): string
    {
        if (preg_match('/"message"\s*:\s*"([^"]+)"/', $raw, $m)) {
            return mb_substr($m[1], 0, 200);
        }

        return mb_substr(trim(preg_replace('/\s+/', ' ', $raw) ?? $raw), 0, 200);
    }

    /**
     * @param  array<string,mixed>  $cfg
     * @return list<string>
     */
    private function listModels(string $provider, array $cfg): array
    {
        try {
            $url = $provider === 'anthropic'
                ? 'https://api.anthropic.com/v1/models?limit=100'
                : rtrim((string) $cfg['base_url'], '/').'/models';

            $headers = $provider === 'anthropic'
                ? ['x-api-key' => $cfg['key'], 'anthropic-version' => '2023-06-01']
                : ['Authorization' => 'Bearer '.$cfg['key']];

            $resp = Http::withHeaders($headers)->timeout(20)->get($url);

            if ($resp->failed()) {
                return [];
            }

            $ids = array_column($resp->json('data') ?? [], 'id');
            sort($ids);

            return array_values(array_filter($ids, 'is_string'));
        } catch (\Throwable) {
            return [];
        }
    }
}
