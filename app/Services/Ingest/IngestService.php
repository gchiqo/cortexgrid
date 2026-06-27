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
     * @param  list<array<string,mixed>>  $records  each: ['external_id'=>?, 'title'=>?, 'text'=>?, ...fields]
     * @return array{source_id:int,documents:int,created:int,updated:int,chunks:int}
     *
     * Records carrying an `external_id` are upserted: an existing document with that id
     * in the dataset is updated + re-embedded instead of duplicated (keeps synced data fresh).
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
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $externalId = isset($record['external_id']) && $record['external_id'] !== ''
                ? (string) $record['external_id']
                : null;
            $fields = Arr::except($record, ['external_id']);

            $text = $this->recordToText($fields);
            if (trim($text) === '') {
                continue;
            }

            $attributes = [
                'source_id' => $source->id,
                'title' => $fields['title'] ?? ($fields['name'] ?? null),
                'raw_text' => $text,
                'structured' => Arr::except($fields, ['text']) ?: null,
            ];

            $existing = $externalId
                ? Document::where('dataset_id', $datasetId)->where('external_id', $externalId)->first()
                : null;

            if ($existing) {
                $existing->update($attributes);
                $existing->chunks()->delete();
                $document = $existing;
                $updated++;
            } else {
                $document = Document::create($attributes + [
                    'tenant_id' => $tenantId,
                    'dataset_id' => $datasetId,
                    'external_id' => $externalId,
                ]);
                $created++;
            }

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
            'created' => $created,
            'updated' => $updated,
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
