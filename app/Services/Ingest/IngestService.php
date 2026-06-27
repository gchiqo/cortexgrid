<?php

namespace App\Services\Ingest;

use App\Jobs\EmbedChunks;
use App\Models\Chunk;
use App\Models\Document;
use App\Models\Source;
use App\Models\UsageEvent;
use App\Services\Rag\Chunker;
use Illuminate\Support\Arr;

/**
 * Shared ingestion pipeline: records -> source/documents/chunks -> dispatch embeddings.
 * Used by the public API (/v1/ingest) and the dashboard file upload.
 */
class IngestService
{
    public function __construct(private Chunker $chunker) {}

    /**
     * @param  list<array<string,mixed>>  $records  each: ['title'=>?, 'text'=>?, ...fields]
     * @return array{source_id:int,documents:int,chunks:int}
     */
    public function ingest(int $tenantId, int $datasetId, string $type, string $sourceName, array $records, ?int $apiKeyId = null, bool $syncEmbed = false): array
    {
        $source = Source::create([
            'tenant_id' => $tenantId,
            'dataset_id' => $datasetId,
            'type' => $type,
            'name' => $sourceName,
            'status' => 'processing',
        ]);

        $documentIds = [];
        $totalChunks = 0;

        foreach ($records as $record) {
            $text = $this->recordToText($record);
            if (trim($text) === '') {
                continue;
            }

            $document = Document::create([
                'source_id' => $source->id,
                'tenant_id' => $tenantId,
                'dataset_id' => $datasetId,
                'title' => $record['title'] ?? ($record['name'] ?? null),
                'raw_text' => $text,
                'structured' => Arr::except($record, ['text']) ?: null,
            ]);

            foreach ($this->chunker->chunk($text) as $i => $piece) {
                Chunk::create([
                    'document_id' => $document->id,
                    'tenant_id' => $tenantId,
                    'dataset_id' => $datasetId,
                    'content' => $piece,
                    'metadata' => ['title' => $document->title, 'chunk_index' => $i, 'source_id' => $source->id],
                ]);
                $totalChunks++;
            }

            $documentIds[] = $document->id;
            $syncEmbed
                ? EmbedChunks::dispatchSync($document->id)
                : EmbedChunks::dispatch($document->id);
        }

        if ($documentIds === []) {
            $source->update(['status' => 'failed']);
        }

        UsageEvent::record($tenantId, 'ingest', count($documentIds), $apiKeyId);

        return [
            'source_id' => $source->id,
            'documents' => count($documentIds),
            'chunks' => $totalChunks,
        ];
    }

    private function recordToText(array $record): string
    {
        if (! empty($record['text']) && is_string($record['text'])) {
            return $record['text'];
        }

        $lines = [];
        foreach ($record as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = is_scalar($value)
                ? "{$key}: {$value}"
                : "{$key}: ".json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $lines);
    }
}
