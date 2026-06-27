<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\EmbedChunks;
use App\Models\Chunk;
use App\Models\Document;
use App\Models\Source;
use App\Models\UsageEvent;
use App\Services\Rag\Chunker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * POST /v1/ingest — generic ingestion endpoint (also used by the WordPress plugin).
 *
 * Accepts either:
 *   { "text": "...", "title": "...", "source_name": "...", "type": "pdf|api|..." }
 * or:
 *   { "records": [ { "title": "...", "text": "...", ...fields } ], "source_name": "...", "type": "wordpress" }
 */
class IngestController extends Controller
{
    public function store(Request $request, Chunker $chunker): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $apiKeyId = optional($request->attributes->get('api_key'))->id;

        $data = $request->validate([
            'text' => ['required_without:records', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'records' => ['required_without:text', 'array'],
            'records.*' => ['array'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $source = Source::create([
            'tenant_id' => $tenantId,
            'type' => $data['type'] ?? ($request->filled('records') ? 'api' : 'text'),
            'name' => $data['source_name'] ?? 'Ingest '.now()->format('Y-m-d H:i'),
            'status' => 'processing',
        ]);

        $records = $request->filled('records')
            ? $data['records']
            : [['title' => $data['title'] ?? null, 'text' => $data['text']]];

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
                'title' => $record['title'] ?? ($record['name'] ?? null),
                'raw_text' => $text,
                'structured' => Arr::except($record, ['text']) ?: null,
            ]);

            $pieces = $chunker->chunk($text);
            foreach ($pieces as $i => $piece) {
                Chunk::create([
                    'document_id' => $document->id,
                    'tenant_id' => $tenantId,
                    'content' => $piece,
                    'metadata' => [
                        'title' => $document->title,
                        'chunk_index' => $i,
                        'source_id' => $source->id,
                    ],
                ]);
            }

            $totalChunks += count($pieces);
            $documentIds[] = $document->id;

            EmbedChunks::dispatch($document->id);
        }

        UsageEvent::record($tenantId, 'ingest', count($documentIds), $apiKeyId);

        return response()->json([
            'source_id' => $source->id,
            'documents' => count($documentIds),
            'chunks' => $totalChunks,
            'status' => 'processing',
            'message' => 'ჩაიტვირთა; ემბედინგები მუშავდება ფონურად.',
        ], 201);
    }

    /** Turn an arbitrary record into a readable text blob for chunking/embedding. */
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
            if (is_scalar($value)) {
                $lines[] = "{$key}: {$value}";
            } else {
                $lines[] = "{$key}: ".json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return implode("\n", $lines);
    }
}
