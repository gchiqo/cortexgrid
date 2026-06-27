<?php

namespace App\Services\Rag;

use App\Models\AiConfig;
use App\Models\UsageEvent;
use App\Services\Anthropic;
use App\Services\Groq;
use App\Services\Tools\ToolRegistry;

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
        private ToolRegistry $tools,
        private Reranker $reranker,
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
        bool $allowTools = false,
    ): array {
        $startedAt = microtime(true);
        $steps = [];

        // Tools run only on trusted surfaces (not the public widget) and only for configs that enable them.
        $toolDefs = ($allowTools && $config && ! empty($config->enabled_tools))
            ? $this->tools->definitions($config->enabled_tools)
            : [];
        $useTools = $toolDefs !== [];

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

        // 2) Hybrid retrieval, scoped to this agent's dataset(s) (with trace when requested).
        $datasetId = $config ? $config->datasetIds() : null;
        $rerank = $config && $config->rerankEnabled();
        $retrieveK = $rerank ? min($k * 3, 18) : $k;
        $retrievalTrace = null;
        $hits = $withTrace
            ? $this->retriever->retrieve($tenantId, $searchQuery, $retrieveK, $datasetId, $retrievalTrace)
            : $this->retriever->retrieve($tenantId, $searchQuery, $retrieveK, $datasetId);

        // 2b) Optional reranking (Groq) over the fused pool → best $k.
        if ($rerank && count($hits) > 1) {
            [$hits, $rr] = $this->reranker->rerank($searchQuery, $hits, $k);
            $steps[] = ['step' => 'rerank'] + $rr;
        }

        // No data AND no tools to act with → nothing to do.
        if ($hits === [] && ! $useTools) {
            UsageEvent::record($tenantId, 'query', 1, $apiKeyId);

            return $this->wrap(
                'ბოდიში, ამ კითხვაზე პასუხი თქვენს მონაცემებში ვერ მოვძებნე.',
                [], $config, 0, 0,
                $withTrace ? $this->trace($searchQuery, $steps, $retrievalTrace, null, count($history), $startedAt) : null,
            );
        }

        [$contextBlock, $sources] = $hits !== []
            ? $this->buildContext($hits)
            : ['(მონაცემებში დამთხვევა არ მოიძებნა — საჭიროების შემთხვევაში გამოიყენე ხელსაწყოები)', []];

        // 3) Grounded answer (+ conversation memory, + tools for admin chatbots).
        $system = $this->systemPrompt($config, $contextBlock);
        if ($useTools) {
            $system .= "\n\nშეგიძლია გამოიყენო ხელსაწყოები მოქმედებების შესასრულებლად (ჩანაწერის დამატება/განახლება/ძებნა). იმოქმედე მხოლოდ მაშინ, როცა მომხმარებელი ამას ნათლად ითხოვს.";
        }
        $messages = $this->buildMessages($history, $question);

        $t0 = microtime(true);
        if ($useTools) {
            $execute = fn (string $n, array $in) => $this->tools->execute($n, $in, $tenantId, (int) $config->dataset_id);
            $result = $this->anthropic->agentChat($system, $messages, $toolDefs, $execute, $config?->modelId());
        } else {
            $result = $this->anthropic->chat(system: $system, messages: $messages, model: $config?->modelId());
            $result['tool_calls'] = [];
        }
        $genMs = round((microtime(true) - $t0) * 1000);

        UsageEvent::record($tenantId, 'query', 1, $apiKeyId);
        UsageEvent::record($tenantId, 'tokens', $result['input_tokens'] + $result['output_tokens'], $apiKeyId);

        $generate = [
            'provider' => 'anthropic',
            'model' => $config?->modelId() ?? config('services.anthropic.model'),
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'ms' => $genMs,
            'tool_calls' => $result['tool_calls'],
        ];

        return $this->wrap(
            $result['text'], $sources, $config,
            $result['input_tokens'], $result['output_tokens'],
            $withTrace ? $this->trace($searchQuery, $steps, $retrievalTrace, $generate, count($history), $startedAt) : null,
            $result['tool_calls'],
        );
    }

    /**
     * Streaming variant for the widget (no tools). Calls $onToken per delta.
     *
     * @param  list<array{role:string,content:string}>  $history
     * @return array{answer:string,sources:list<array<string,mixed>>,usage:array{input_tokens:int,output_tokens:int},answered:bool}
     */
    public function answerStream(int $tenantId, string $question, ?AiConfig $config, array $history, callable $onToken, ?int $apiKeyId = null): array
    {
        $searchQuery = $history !== [] ? $this->rewriteQuery($question, $history) : $question;
        $datasetId = $config ? $config->datasetIds() : null;
        $rerank = $config && $config->rerankEnabled();

        $hits = $this->retriever->retrieve($tenantId, $searchQuery, $rerank ? 18 : 6, $datasetId);
        if ($rerank && count($hits) > 1) {
            [$hits] = $this->reranker->rerank($searchQuery, $hits, 6);
        }

        if ($hits === []) {
            UsageEvent::record($tenantId, 'query', 1, $apiKeyId);
            $msg = 'ბოდიში, ამ კითხვაზე პასუხი თქვენს მონაცემებში ვერ მოვძებნე.';
            $onToken($msg);

            return ['answer' => $msg, 'sources' => [], 'usage' => ['input_tokens' => 0, 'output_tokens' => 0], 'answered' => false];
        }

        [$contextBlock, $sources] = $this->buildContext($hits);
        $system = $this->systemPrompt($config, $contextBlock);
        $messages = $this->buildMessages($history, $question);

        $result = $this->anthropic->stream($system, $messages, $config?->modelId(), 1500, $onToken);

        UsageEvent::record($tenantId, 'query', 1, $apiKeyId);
        UsageEvent::record($tenantId, 'tokens', $result['input_tokens'] + $result['output_tokens'], $apiKeyId);

        return [
            'answer' => $result['text'],
            'sources' => $sources,
            'usage' => ['input_tokens' => $result['input_tokens'], 'output_tokens' => $result['output_tokens']],
            'answered' => true,
        ];
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
    private function wrap(string $answer, array $sources, ?AiConfig $config, int $in, int $out, ?array $trace, array $toolCalls = []): array
    {
        $payload = [
            'answer' => $answer,
            'sources' => $sources,
            'config' => $config?->name,
            'usage' => ['input_tokens' => $in, 'output_tokens' => $out],
        ];
        if ($toolCalls !== []) {
            $payload['tools'] = $toolCalls;
        }
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

        // Pull title + url from the source documents for clickable citations.
        $docIds = array_values(array_unique(array_map(fn ($h) => $h['document_id'], $hits)));
        $docs = \App\Models\Document::whereIn('id', $docIds)->get(['id', 'title', 'structured'])->keyBy('id');

        foreach ($hits as $i => $hit) {
            $ref = $i + 1;
            $doc = $docs[$hit['document_id']] ?? null;
            $title = $hit['metadata']['title'] ?? $doc?->title;
            $url = $doc?->structured['url'] ?? null;
            $header = $title ? "[#{$ref}] {$title}" : "[#{$ref}]";
            $lines[] = $header."\n".$hit['content'];

            $sources[] = [
                'ref' => $ref,
                'document_id' => $hit['document_id'],
                'title' => $title,
                'url' => $url,
                'snippet' => mb_substr($hit['content'], 0, 200),
                'score' => $hit['score'],
            ];
        }

        return [implode("\n\n---\n\n", $lines), $sources];
    }
}
