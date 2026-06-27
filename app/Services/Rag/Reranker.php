<?php

namespace App\Services\Rag;

use App\Services\Groq;

/**
 * LLM reranker — re-scores the fused candidates against the query with Groq (fast/cheap)
 * and keeps the best $k. Falls back to the original order on any failure.
 */
class Reranker
{
    public function __construct(private Groq $groq) {}

    /**
     * @param  list<array<string,mixed>>  $hits
     * @return array{0: list<array<string,mixed>>, 1: array<string,mixed>}  [reranked top-k, trace]
     */
    public function rerank(string $query, array $hits, int $k): array
    {
        if (count($hits) <= 1) {
            return [array_slice($hits, 0, $k), ['used' => false]];
        }

        $t0 = microtime(true);
        $lines = [];
        foreach ($hits as $i => $h) {
            $lines[] = ($i + 1).'. '.mb_substr((string) $h['content'], 0, 200);
        }

        $messages = [
            ['role' => 'system', 'content' => 'შენ ხარ რელევანტურობის შემფასებელი. დააბრუნე მხოლოდ JSON.'],
            ['role' => 'user', 'content' => "კითხვა: {$query}\n\nპასაჟები:\n".implode("\n", $lines)
                ."\n\nშეაფასე თითო პასაჟის რელევანტურობა კითხვასთან 0-10 ქულით. დააბრუნე JSON მასივი: [{\"n\":1,\"score\":8}, ...]"],
        ];

        try {
            $scores = $this->parseScores($this->groq->chat($messages, temperature: 0.0));
            if ($scores === []) {
                throw new \RuntimeException('empty rerank');
            }

            $ranked = [];
            foreach ($hits as $i => $h) {
                $h['rerank_score'] = $scores[$i + 1] ?? 0.0;
                $ranked[] = $h;
            }
            usort($ranked, fn ($a, $b) => $b['rerank_score'] <=> $a['rerank_score']);
            $top = array_slice($ranked, 0, $k);

            return [$top, [
                'used' => true,
                'provider' => 'groq',
                'ms' => round((microtime(true) - $t0) * 1000),
                'pool' => count($hits),
                'kept' => count($top),
            ]];
        } catch (\Throwable $e) {
            report($e);

            return [array_slice($hits, 0, $k), ['used' => false, 'error' => true]];
        }
    }

    /** @return array<int,float> n => score */
    private function parseScores(string $text): array
    {
        $start = strpos($text, '[');
        $end = strrpos($text, ']');
        if ($start === false || $end === false || $end < $start) {
            return [];
        }
        $arr = json_decode(substr($text, $start, $end - $start + 1), true);
        if (! is_array($arr)) {
            return [];
        }

        $out = [];
        foreach ($arr as $item) {
            if (isset($item['n'])) {
                $out[(int) $item['n']] = (float) ($item['score'] ?? 0);
            }
        }

        return $out;
    }
}
