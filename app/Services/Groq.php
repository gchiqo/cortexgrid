<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Groq — fast/cheap inference (OpenAI-compatible) for the hot path:
 * query rewriting, classification, drafts. (Whisper STT can use the same key.)
 */
class Groq
{
    private string $key;

    private string $base;

    private string $model;

    public function __construct()
    {
        $this->key = (string) config('services.groq.key');
        $this->base = rtrim((string) config('services.groq.base_url'), '/');
        $this->model = (string) config('services.groq.model');
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public function chat(array $messages, ?string $model = null, float $temperature = 0.2): string
    {
        $resp = Http::withToken($this->key)
            ->timeout(60)
            ->retry(2, 500)
            ->post($this->base.'/chat/completions', [
                'model' => $model ?? $this->model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

        if ($resp->failed()) {
            throw new RuntimeException('Groq chat failed: '.$resp->status().' '.$resp->body());
        }

        return (string) $resp->json('choices.0.message.content', '');
    }
}
