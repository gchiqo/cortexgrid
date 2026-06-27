<?php

namespace App\Services\Tools;

use App\Jobs\EmbedChunks;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\Ingest\IngestService;
use App\Services\Rag\Chunker;
use App\Services\Rag\Retriever;

/**
 * Executable tools an (admin) chatbot can call. Every tool is scoped to the
 * chatbot's dataset + tenant, so a chatbot can only act on its own data.
 */
class ToolRegistry
{
    public function __construct(
        private IngestService $ingest,
        private Retriever $retriever,
        private Chunker $chunker,
    ) {}

    /**
     * Tool definitions (Anthropic format) for the given enabled tool names.
     *
     * @param  list<string>  $enabled
     * @return list<array<string,mixed>>
     */
    public function definitions(array $enabled): array
    {
        $all = $this->schemas();

        return array_values(array_filter(array_map(
            fn (string $name) => $all[$name] ?? null,
            $enabled
        )));
    }

    /** @param array<string,mixed> $input */
    public function execute(string $name, array $input, int $tenantId, int $datasetId): string
    {
        return match ($name) {
            'add_item' => $this->addItem($input, $tenantId, $datasetId),
            'update_item' => $this->updateItem($input, $tenantId, $datasetId),
            'find_items' => $this->findItems($input, $tenantId, $datasetId),
            default => "უცნობი ხელსაწყო: {$name}",
        };
    }

    /** @return array<string,array<string,mixed>> */
    private function schemas(): array
    {
        return [
            'add_item' => [
                'name' => 'add_item',
                'description' => 'ამ ჩატბოტის დატასეტში ახალი ჩანაწერის დამატება (პროდუქტი, სტატია, ფილმი და ა.შ.). ჩანაწერი მაშინვე ხდება ძებნადი.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'ჩანაწერის სათაური ან სახელი'],
                        'content' => ['type' => 'string', 'description' => 'სრული ტექსტი / აღწერა (ყველა მნიშვნელოვანი ველი)'],
                    ],
                    'required' => ['title', 'content'],
                ],
            ],
            'update_item' => [
                'name' => 'update_item',
                'description' => 'არსებული ჩანაწერის ტექსტის განახლება id-ით. id-ის მოსაძებნად ჯერ გამოიყენე find_items.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'ჩანაწერის (დოკუმენტის) id'],
                        'content' => ['type' => 'string', 'description' => 'განახლებული სრული ტექსტი'],
                    ],
                    'required' => ['id', 'content'],
                ],
            ],
            'find_items' => [
                'name' => 'find_items',
                'description' => 'ჩანაწერების ძებნა ამ დატასეტში. აბრუნებს id-ს, სათაურს და ფრაგმენტს — გამოსადეგია განახლების ან დაკავშირების წინ.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'საძიებო ფრაზა'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    /** @param array<string,mixed> $input */
    private function addItem(array $input, int $tenantId, int $datasetId): string
    {
        $title = trim((string) ($input['title'] ?? ''));
        $content = trim((string) ($input['content'] ?? ''));
        if ($title === '' || $content === '') {
            return 'საჭიროა title და content.';
        }

        $summary = $this->ingest->ingest(
            $tenantId, $datasetId, 'chatbot', 'ჩატბოტის დამატებული',
            [['title' => $title, 'text' => $content]],
            null, true, // syncEmbed
        );

        $doc = Document::where('dataset_id', $datasetId)->latest('id')->first();

        return "დაემატა «{$title}» (id={$doc?->id}, {$summary['chunks']} ჩანკი, ძებნადია).";
    }

    /** @param array<string,mixed> $input */
    private function updateItem(array $input, int $tenantId, int $datasetId): string
    {
        $id = (int) ($input['id'] ?? 0);
        $content = trim((string) ($input['content'] ?? ''));

        $doc = Document::where('dataset_id', $datasetId)->where('tenant_id', $tenantId)->find($id);
        if (! $doc) {
            return "ჩანაწერი id={$id} ვერ მოიძებნა ამ დატასეტში.";
        }
        if ($content === '') {
            return 'საჭიროა content.';
        }

        $doc->update(['raw_text' => $content]);
        $doc->chunks()->delete();

        foreach ($this->chunker->chunk($content) as $i => $piece) {
            Chunk::create([
                'document_id' => $doc->id,
                'tenant_id' => $tenantId,
                'dataset_id' => $datasetId,
                'content' => $piece,
                'metadata' => ['title' => $doc->title, 'chunk_index' => $i],
            ]);
        }

        EmbedChunks::dispatchSync($doc->id);

        return "განახლდა «{$doc->title}» (id={$id}).";
    }

    /** @param array<string,mixed> $input */
    private function findItems(array $input, int $tenantId, int $datasetId): string
    {
        $query = trim((string) ($input['query'] ?? ''));
        if ($query === '') {
            return 'საჭიროა query.';
        }

        $hits = $this->retriever->retrieve($tenantId, $query, 6, $datasetId);
        if ($hits === []) {
            return 'ვერაფერი მოიძებნა.';
        }

        $lines = [];
        $seen = [];
        foreach ($hits as $hit) {
            $docId = $hit['document_id'];
            if (isset($seen[$docId])) {
                continue;
            }
            $seen[$docId] = true;
            $title = $hit['metadata']['title'] ?? "#{$docId}";
            $lines[] = "id={$docId} — {$title}: ".mb_substr($hit['content'], 0, 80);
        }

        return implode("\n", $lines);
    }
}
