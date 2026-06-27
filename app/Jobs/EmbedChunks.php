<?php

namespace App\Jobs;

use App\Models\Chunk;
use App\Models\Document;
use App\Services\Gemini;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Embeds all not-yet-embedded chunks of a document via Gemini and marks the source ready.
 */
class EmbedChunks implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $documentId) {}

    public function handle(Gemini $gemini): void
    {
        $document = Document::with('source')->find($this->documentId);
        if (! $document) {
            return;
        }

        Chunk::query()
            ->where('document_id', $this->documentId)
            ->whereNull('embedding')
            ->orderBy('id')
            ->chunkById(50, function ($chunks) use ($gemini) {
                $vectors = $gemini->embedTexts($chunks->pluck('content')->all());

                foreach ($chunks->values() as $i => $chunk) {
                    if (! isset($vectors[$i])) {
                        continue;
                    }
                    $chunk->embedding = $vectors[$i];
                    $chunk->save();
                }
            });

        $document->source?->update(['status' => 'ready']);
    }
}
