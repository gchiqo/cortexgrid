<?php

namespace App\Services\Rag;

use App\Models\AiConfig;
use App\Models\UsageEvent;
use App\Services\Anthropic;

/**
 * Shared "ask my data" pipeline: hybrid retrieve -> grounded Georgian answer with citations.
 * Used by both the public API (/v1/query) and the dashboard test-chat.
 */
class AskService
{
    private const DEFAULT_PROMPT =
        'შენ ხარ დამხმარე ასისტენტი, რომელიც პასუხობს მომხმარებლის კითხვებზე მის მონაცემებზე დაყრდნობით.';

    public function __construct(
        private Retriever $retriever,
        private Anthropic $anthropic,
    ) {}

    /**
     * @return array{answer:string,sources:list<array<string,mixed>>,config:?string,usage:array{input_tokens:int,output_tokens:int}}
     */
    public function answer(int $tenantId, string $question, ?AiConfig $config = null, int $k = 6, ?int $apiKeyId = null): array
    {
        $hits = $this->retriever->retrieve($tenantId, $question, $k);

        if ($hits === []) {
            UsageEvent::record($tenantId, 'query', 1, $apiKeyId);

            return [
                'answer' => 'ბოდიში, ამ კითხვაზე პასუხი თქვენს მონაცემებში ვერ მოვძებნე.',
                'sources' => [],
                'config' => $config?->name,
                'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
            ];
        }

        [$contextBlock, $sources] = $this->buildContext($hits);

        $system = trim(($config?->system_prompt ?: self::DEFAULT_PROMPT))."\n\n"
            ."წესები:\n"
            ."- უპასუხე მხოლოდ ქართულ ენაზე.\n"
            ."- გამოიყენე მხოლოდ ქვემოთ მოცემული კონტექსტი. ნუ მოიგონებ ფაქტებს.\n"
            ."- თუ პასუხი კონტექსტში არ არის, პირდაპირ თქვი რომ ინფორმაცია ვერ მოიძებნა.\n"
            ."- პასუხის ბოლოს მიუთითე გამოყენებული წყაროები ფორმატით [#ნომერი].\n\n"
            ."კონტექსტი:\n".$contextBlock;

        $result = $this->anthropic->chat(
            system: $system,
            userMessage: $question,
            model: $config?->modelId(),
        );

        UsageEvent::record($tenantId, 'query', 1, $apiKeyId);
        UsageEvent::record($tenantId, 'tokens', $result['input_tokens'] + $result['output_tokens'], $apiKeyId);

        return [
            'answer' => $result['text'],
            'sources' => $sources,
            'config' => $config?->name,
            'usage' => [
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ],
        ];
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
