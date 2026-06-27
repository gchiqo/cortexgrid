<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Builds a "what the platform understood" profile of a dataset:
 *  - facets (brands, categories, price ranges…) aggregated from documents' structured fields (no LLM)
 *  - an optional AI analysis (summary, relationships, missing info) via one Claude call
 */
class KnowledgeProfiler
{
    public function __construct(private Anthropic $anthropic) {}

    /** Overview counts + facets derived purely from structured data. */
    public function facets(int $datasetId): array
    {
        $overview = [
            'documents' => (int) DB::table('documents')->where('dataset_id', $datasetId)->count(),
            'chunks' => (int) DB::table('chunks')->where('dataset_id', $datasetId)->count(),
            'embedded' => (int) DB::table('chunks')->where('dataset_id', $datasetId)->whereNotNull('embedding')->count(),
            'sources' => (int) DB::table('sources')->where('dataset_id', $datasetId)->count(),
        ];

        // (key, value) pairs from up to 500 documents' structured jsonb.
        $rows = DB::select(
            'select e.key as k, e.value as v
             from (select structured from documents where dataset_id = ? and structured is not null order by id limit 500) d,
                  lateral jsonb_each_text(d.structured) e',
            [$datasetId]
        );

        $byKey = [];
        foreach ($rows as $r) {
            $v = trim((string) $r->v);
            if ($v === '') {
                continue;
            }
            $byKey[$r->k][$v] = ($byKey[$r->k][$v] ?? 0) + 1;
        }

        $facets = [];
        foreach ($byKey as $key => $counts) {
            $distinct = count($counts);
            $total = array_sum($counts);
            $numeric = $this->numericValues(array_keys($counts));

            if ($numeric !== null) {
                $facets[] = [
                    'key' => $key, 'type' => 'numeric',
                    'min' => min($numeric), 'max' => max($numeric),
                    'avg' => round(array_sum($numeric) / count($numeric), 1), 'count' => $total,
                ];
            } elseif ($distinct <= 25) {
                arsort($counts);
                $top = array_slice($counts, 0, 12, true);
                $facets[] = [
                    'key' => $key, 'type' => 'facet', 'distinct' => $distinct,
                    'values' => array_map(fn ($val, $c) => ['value' => $val, 'count' => $c], array_keys($top), array_values($top)),
                ];
            } else {
                $facets[] = ['key' => $key, 'type' => 'text', 'distinct' => $distinct];
            }
        }

        // facets first, free-text fields last
        usort($facets, fn ($a, $b) => ($a['type'] === 'text' ? 1 : 0) <=> ($b['type'] === 'text' ? 1 : 0));

        return ['overview' => $overview, 'facets' => $facets];
    }

    /** One Claude call → ['business_summary', 'relationships'=>[{from,to,type}], 'missing_info'=>[...]]. */
    public function analyze(int $datasetId): array
    {
        $samples = DB::table('documents')
            ->where('dataset_id', $datasetId)->whereNotNull('structured')
            ->orderBy('id')->limit(20)->pluck('structured')
            ->map(fn ($s) => mb_substr((string) $s, 0, 300))->implode("\n");

        if (trim($samples) === '') {
            return ['business_summary' => null, 'relationships' => [], 'missing_info' => []];
        }

        $system = 'შენ აანალიზებ მონაცემთა ნაკრების ჩანაწერებს და აღწერ რა "ესმის" სისტემას. დააბრუნე მხოლოდ ვალიდური JSON.';
        $user = "ჩანაწერების ნიმუში (JSON):\n{$samples}\n\n"
            ."გააანალიზე და დააბრუნე ზუსტად ამ ფორმატით (მოკლედ, ქართულად):\n"
            .'{"business_summary":"ერთი წინადადება","relationships":[{"from":"ველი","to":"ველი","type":"კავშირის ტიპი"}],"missing_info":["რა ინფორმაცია აკლია"]}';

        $res = $this->anthropic->chat(system: $system, messages: [['role' => 'user', 'content' => $user]], maxTokens: 1500);
        $data = $this->extractJson($res['text']);

        return [
            'business_summary' => $data['business_summary'] ?? null,
            'relationships' => array_slice($data['relationships'] ?? [], 0, 8),
            'missing_info' => array_slice($data['missing_info'] ?? [], 0, 8),
        ];
    }

    /** Returns the numeric values if ≥80% of distinct values are numbers, else null. */
    private function numericValues(array $values): ?array
    {
        $nums = [];
        foreach ($values as $v) {
            $clean = str_replace([' ', ','], ['', '.'], (string) $v);
            if (is_numeric($clean)) {
                $nums[] = (float) $clean;
            }
        }

        return (count($values) >= 2 && count($nums) >= 0.8 * count($values)) ? $nums : null;
    }

    private function extractJson(string $text): array
    {
        $t = trim(preg_replace('/```(?:json)?/i', '', $text) ?? $text);
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start === false || $end === false || $end < $start) {
            return [];
        }
        $data = json_decode(substr($t, $start, $end - $start + 1), true);

        return is_array($data) ? $data : [];
    }
}
