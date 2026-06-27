<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Gemini — used for embeddings (the vector path) and multimodal/text generation.
 */
class Gemini
{
    private string $key;

    private string $base;

    private string $embeddingModel;

    private string $genModel;

    public function __construct()
    {
        $this->key = (string) config('services.gemini.key');
        $this->base = rtrim((string) config('services.gemini.base_url'), '/');
        $this->embeddingModel = (string) config('services.gemini.embedding_model');
        $this->genModel = (string) config('services.gemini.model');
    }

    /**
     * Embed many texts in one call.
     *
     * @param  list<string>  $texts
     * @return list<list<float>>  one vector per input text
     */
    public function embedTexts(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        // gemini-embedding-001 supports only embedContent (no batch endpoint);
        // outputDimensionality reduces the 3072-d default to our column size (768).
        $dim = (int) config('services.gemini.embedding_dim', 768);
        $url = "{$this->base}/models/{$this->embeddingModel}:embedContent";

        $out = [];
        foreach ($texts as $t) {
            $resp = Http::timeout(60)
                ->retry(2, 500)
                ->post($url.'?key='.$this->key, [
                    'model' => "models/{$this->embeddingModel}",
                    'content' => ['parts' => [['text' => $t]]],
                    'outputDimensionality' => $dim,
                ]);

            if ($resp->failed()) {
                throw new RuntimeException('Gemini embedding failed: '.$resp->status().' '.$resp->body());
            }

            $out[] = array_map('floatval', $resp->json('embedding.values', []));
        }

        return $out;
    }

    /** Embed a single query string. */
    public function embedQuery(string $text): array
    {
        return $this->embedTexts([$text])[0] ?? [];
    }

    /**
     * Plain text generation (used for PDF/image extraction or fallback answers).
     *
     * @param  array<int,array<string,mixed>>  $parts  Gemini "parts" (text and/or inline_data)
     */
    public function generate(array $parts, ?string $system = null): string
    {
        $body = ['contents' => [['role' => 'user', 'parts' => $parts]]];

        if ($system !== null && $system !== '') {
            $body['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        $url = "{$this->base}/models/{$this->genModel}:generateContent";

        $resp = Http::timeout(120)
            ->retry(1, 1000)
            ->post($url.'?key='.$this->key, $body);

        if ($resp->failed()) {
            throw new RuntimeException('Gemini generate failed: '.$resp->status().' '.$resp->body());
        }

        return (string) $resp->json('candidates.0.content.parts.0.text', '');
    }

    /** Convenience: generate from a plain prompt string. */
    public function generateText(string $prompt, ?string $system = null): string
    {
        return $this->generate([['text' => $prompt]], $system);
    }
}
