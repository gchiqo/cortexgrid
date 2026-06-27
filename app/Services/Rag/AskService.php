<?php

namespace App\Services\Rag;

use App\Models\AiConfig;
use App\Models\UsageEvent;
use App\Services\Anthropic;
use App\Services\Groq;

/**
 * Shared "ask my data" pipeline: (optional history-aware query rewrite) -> hybrid retrieve
 * -> grounded Georgian answer with citations. Used by the API, dashboard, widget, and console.
 */
class AskService
{
    private const DEFAULT_PROMPT =
        'შენ ხარ დამხმარე ასისტენტი, რომელიც პასუხობს მომხმარებლის კითხვებზე მის მონაცემებზე დაყრდნობით.';

    /** How many prior turns to feed the model for conversational context. */
    private const HISTORY_TURNS = 6;

    public function __construct(
        private Retriever $retriever,
        private Anthropic $anthropic,
        private Groq $groq,
    ) {}

    /**
     * @param  list<array{role:string,content:string}>  $history  prior turns (oldest first)
     * @return array{answer:string,sources:list<array<string,mixed>>,config:?string,usage:array{input_tokens:int,output_tokens:int},trace?:array}
     */
    public function answer(
        int $tenantId,
        string $question,
        ?AiConfig $config = null,
        int $k = 6,
        ?int $apiKeyId = null,
        array $history = [],
        bool $withTrace = false,
    ): array {
        $startedAt = microtime(true);
        $steps = [];

        // 1) History-aware query rewrite (so follow-ups like "და ის?" retrieve correctly).
        $searchQuery = $question;
        if ($history !== []) {
            $t0 = microtime(true);
            $searchQuery = $this->rewriteQuery($question, $history);
            $steps[] = [
                'step' => 'query_rewrite',
                'provider' => 'groq',
                'from' => $question,
                'to' => $searchQuery,
                'ms' => round((microtime(true) - $t0) * 1000),
            ];
        }

        // 2) Hybrid retrieval (with trace when requested).
        $retrievalTrace = null;
        $hits = $withTrace
            ? $this->retriever->retrieve($tenantId, $searchQuery, $k, $retrievalTrace)
            : $this->retriever->retrieve($tenantId, $searchQuery, $k);

        if ($hits === []) {
            UsageEvent::record($tenantId, 'query', 1, $apiKeyId);

            return $this->wrap(
                'ბოდიში, ამ კითხვაზე პასუხი თქვენს მონაცემებში ვერ მოვძებნე.',
                [], $config, 0, 0,
                $withTrace ? $this->trace($searchQuery, $steps, $retrievalTrace, null, count($history), $startedAt) : null,
            );
        }

        [$contextBlock, $sources] = $this->buildContext($hits);

        // 3) Grounded answer (with conversation memory).
        $system = $this->systemPrompt($config, $contextBlock);
        $messages = $this->buildMessages($history, $question);

        $t0 = microtime(true);
        $result = $this->anthropic->chat(system: $system, messages: $messages, model: $config?->modelId());
        $genMs = round((microtime(true) - $t0) * 1000);

        UsageEvent::record($tenantId, 'query', 1, $apiKeyId);
        UsageEvent::record($tenantId, 'tokens', $result['input_tokens'] + $result['output_tokens'], $apiKeyId);

        $generate = [
            'provider' => 'anthropic',
            'model' => $config?->modelId() ?? config('services.anthropic.model'),
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'ms' => $genMs,
        ];

        return $this->wrap(
            $result['text'], $sources, $config,
            $result['input_tokens'], $result['output_tokens'],
            $withTrace ? $this->trace($searchQuery, $steps, $retrievalTrace, $generate, count($history), $startedAt) : null,
        );
    }

    private function rewriteQuery(string $question, array $history): string
    {
        $convo = collect($history)
            ->map(fn ($m) => ($m['role'] === 'user' ? 'მომხმარებელი' : 'ასისტენტი').': '.$m['content'])
            ->implode("\n");

        try {
            $rewritten = trim($this->groq->chat([
                ['role' => 'system', 'content' => 'მოცემული საუბრის გათვალისწინებით გადააკეთე მომხმარებლის ბოლო შეკითხვა დამოუკიდებელ საძიებო ფრაზად იმავე ენაზე. დააბრუნე მხოლოდ ფრაზა, ახსნის გარეშე.'],
                ['role' => 'user', 'content' => "საუბარი:\n{$convo}\n\nბოლო შეკითხვა: {$question}\n\nდამოუკიდებელი საძიებო ფრაზა:"],
            ], temperature: 0.0));

            return $rewritten !== '' ? $rewritten : $question;
        } catch (\Throwable $e) {
            report($e);

            return $question;
        }
    }

    private function systemPrompt(?AiConfig $config, string $contextBlock): string
    {
        return trim(($config?->system_prompt ?: self::DEFAULT_PROMPT))."\n\n"
            ."წესები:\n"
            ."- უპასუხე მხოლოდ ქართულ ენაზე.\n"
            ."- გამოიყენე მხოლოდ ქვემოთ მოცემული კონტექსტი. ნუ მოიგონებ ფაქტებს.\n"
            ."- გაითვალისწინე საუბრის წინა შეტყობინებები follow-up კითხვებისთვის.\n"
            ."- თუ პასუხი კონტექსტში არ არის, პირდაპირ თქვი რომ ინფორმაცია ვერ მოიძებნა.\n"
            ."- პასუხის ბოლოს მიუთითე გამოყენებული წყაროები ფორმატით [#ნომერი].\n\n"
            ."კონტექსტი:\n".$contextBlock;
    }

    /**
     * @param  list<array{role:string,content:string}>  $history
     * @return list<array{role:string,content:string}>
     */
    private function buildMessages(array $history, string $question): array
    {
        $recent = array_slice(array_values($history), -self::HISTORY_TURNS);

        $messages = [];
        foreach ($recent as $m) {
            $role = ($m['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => (string) ($m['content'] ?? '')];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }

    /**
     * @return array{answer:string,sources:array,config:?string,usage:array{input_tokens:int,output_tokens:int},trace?:array}
     */
    private function wrap(string $answer, array $sources, ?AiConfig $config, int $in, int $out, ?array $trace): array
    {
        $payload = [
            'answer' => $answer,
            'sources' => $sources,
            'config' => $config?->name,
            'usage' => ['input_tokens' => $in, 'output_tokens' => $out],
        ];
        if ($trace !== null) {
            $payload['trace'] = $trace;
        }

        return $payload;
    }

    private function trace(string $searchQuery, array $steps, ?array $retrieval, ?array $generate, int $historyTurns, float $startedAt): array
    {
        return array_filter([
            'search_query' => $searchQuery,
            'history_turns' => $historyTurns,
            'steps' => $steps,
            'retrieval' => $retrieval,
            'generate' => $generate,
            'total_ms' => round((microtime(true) - $startedAt) * 1000),
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  list<array{id:int,document_id:int,content:string,metadata:?array,score:float}>  $hits
     * @return array{0:string,1:list<array<string,mixed>>}
     */
    private function buildContext(array $hits): array
    {
        $lines = [];
        $sources = [];

        foreach ($hits as $i => $hit) {
            $ref = $i + 1;
            $title = $hit['metadata']['title'] ?? null;
            $header = $title ? "[#{$ref}] {$title}" : "[#{$ref}]";
            $lines[] = $header."\n".$hit['content'];

            $sources[] = [
                'ref' => $ref,
                'document_id' => $hit['document_id'],
                'title' => $title,
                'snippet' => mb_substr($hit['content'], 0, 200),
                'score' => $hit['score'],
            ];
        }

        return [implode("\n\n---\n\n", $lines), $sources];
    }
}
