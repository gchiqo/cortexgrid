<?php

namespace App\Services;

use App\Models\Chunk;

/**
 * Samples a tenant's ingested data and asks Claude to propose ready-made
 * Georgian chatbot configurations the user can accept or tweak.
 */
class ConfigSuggester
{
    public function __construct(private Anthropic $anthropic) {}

    /**
     * @return array{business_summary:?string, configs:list<array{name:string,system_prompt:string,model_tier:string,rationale:string}>}
     */
    public function suggest(int $datasetId, int $count = 3): array
    {
        $samples = Chunk::where('dataset_id', $datasetId)
            ->inRandomOrder()
            ->limit(10)
            ->pluck('content')
            ->map(fn ($c) => mb_substr((string) $c, 0, 220))
            ->implode("\n---\n");

        if (trim($samples) === '') {
            return ['business_summary' => null, 'configs' => []];
        }

        $system = 'შენ აანალიზებ ბიზნესის მონაცემების ნიმუშს და სთავაზობ მზა ჩატბოტის კონფიგურაციებს ქართულად. '
            .'დააბრუნე მხოლოდ ვალიდური, სრული JSON, დამატებითი ტექსტის გარეშე.';

        $user = "მონაცემების ნიმუში:\n{$samples}\n\n"
            ."შემოგვთავაზე მაქსიმუმ {$count} განსხვავებული, სასარგებლო ჩატბოტის კონფიგურაცია ამ ბიზნესისთვის.\n"
            ."იყავი მოკლე: business_summary — ერთი წინადადება; თითო system_prompt — მაქს. 2 წინადადება; rationale — ერთი წინადადება.\n"
            ."დააბრუნე ზუსტად ამ JSON ფორმატით (და დაასრულე სრულად):\n"
            .'{"business_summary":"...","configs":[{"name":"...","system_prompt":"...","model_tier":"standard","rationale":"..."}]}'."\n"
            .'წესები: name, system_prompt და rationale — ქართულად; model_tier ერთ-ერთი: fast | standard | max.';

        $res = $this->anthropic->chat(
            system: $system,
            messages: [['role' => 'user', 'content' => $user]],
            maxTokens: 4000,
        );

        $data = $this->extractJson($res['text']);

        $configs = [];
        foreach (array_slice($data['configs'] ?? [], 0, $count) as $c) {
            if (empty($c['name']) || empty($c['system_prompt'])) {
                continue;
            }
            $configs[] = [
                'name' => (string) $c['name'],
                'system_prompt' => (string) $c['system_prompt'],
                'model_tier' => in_array($c['model_tier'] ?? '', ['fast', 'standard', 'max'], true) ? $c['model_tier'] : 'standard',
                'rationale' => (string) ($c['rationale'] ?? ''),
            ];
        }

        return [
            'business_summary' => $data['business_summary'] ?? null,
            'configs' => $configs,
        ];
    }

    /** Tolerant JSON extraction (handles ```json fences / stray prose). */
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
