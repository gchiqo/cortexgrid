<?php

namespace App\Services\Rag;

use App\Services\Gemini;
use Illuminate\Support\Facades\DB;

/**
 * Hybrid retrieval: semantic (pgvector cosine) + lexical (Postgres full-text / BM25-ish),
 * fused with Reciprocal Rank Fusion. All queries are tenant-scoped.
 */
class Retriever
{
    public function __construct(private Gemini $gemini) {}

    /**
     * @param  array|null  $trace  when passed by reference, filled with a glass-box trace
     * @return list<array{id:int,document_id:int,content:string,metadata:?array,score:float}>
     */
    public function retrieve(int $tenantId, string $query, int $k = 6, ?array &$trace = null): array
    {
        $k = max(1, min($k, 50));
        $pool = $k * 3;
        $collect = func_num_args() >= 4;

        // --- semantic (degrades to lexical-only if embeddings are unavailable) ---
        $semantic = [];
        $dims = 0;
        $embedMs = 0.0;
        try {
            $t0 = microtime(true);
            $qvec = $this->gemini->embedQuery($query);
            $embedMs = (microtime(true) - $t0) * 1000;
            $dims = count($qvec);
            if ($qvec !== []) {
                $literal = '['.implode(',', $qvec).']';
                $t0 = microtime(true);
                $semantic = DB::select(
                    'select id, document_id, content, metadata,
                            1 - (embedding <=> ?::vector) as score
                     from chunks
                     where tenant_id = ? and embedding is not null
                     order by embedding <=> ?::vector
                     limit '.(int) $pool,
                    [$literal, $tenantId, $literal]
                );
                $semMs = (microtime(true) - $t0) * 1000;
            }
        } catch (\Throwable $e) {
            report($e); // keep going with lexical results only
        }

        // --- lexical (BM25-ish) ---
        $t0 = microtime(true);
        $lexical = DB::select(
            "select id, document_id, content, metadata,
                    ts_rank(content_tsv, plainto_tsquery('simple', ?)) as score
             from chunks
             where tenant_id = ? and content_tsv @@ plainto_tsquery('simple', ?)
             order by score desc
             limit ".(int) $pool,
            [$query, $tenantId, $query]
        );
        $lexMs = (microtime(true) - $t0) * 1000;

        $fused = $this->fuse([$semantic, $lexical], $k);

        if ($collect) {
            $trace = [
                'embedding' => [
                    'provider' => 'gemini',
                    'model' => config('services.gemini.embedding_model'),
                    'dims' => $dims,
                    'ms' => round($embedMs),
                ],
                'semantic' => [
                    'count' => count($semantic),
                    'ms' => round($semMs ?? 0),
                    'candidates' => $this->candidates($semantic),
                ],
                'lexical' => [
                    'count' => count($lexical),
                    'ms' => round($lexMs),
                    'candidates' => $this->candidates($lexical),
                ],
                'fused' => [
                    'method' => 'Reciprocal Rank Fusion',
                    'chosen' => array_map(fn ($h) => [
                        'id' => $h['id'],
                        'score' => $h['score'],
                        'title' => $h['metadata']['title'] ?? null,
                    ], $fused),
                ],
            ];
        }

        return $fused;
    }

    /** @return list<array{id:int,score:float,snippet:string}> */
    private function candidates(array $rows, int $limit = 5): array
    {
        return array_map(fn ($r) => [
            'id' => (int) $r->id,
            'score' => round((float) $r->score, 4),
            'snippet' => mb_substr((string) $r->content, 0, 90),
        ], array_slice($rows, 0, $limit));
    }

    /**
     * Reciprocal Rank Fusion.
     *
     * @param  array<int,array<int,object>>  $lists
     * @return list<array{id:int,document_id:int,content:string,metadata:?array,score:float}>
     */
    private function fuse(array $lists, int $k, int $c = 60): array
    {
        $scores = [];
        $rows = [];

        foreach ($lists as $list) {
            $rank = 0;
            foreach ($list as $row) {
                $rank++;
                $id = (int) $row->id;
                $scores[$id] = ($scores[$id] ?? 0.0) + 1.0 / ($c + $rank);
                $rows[$id] = $row;
            }
        }

        arsort($scores);

        $out = [];
        foreach (array_slice(array_keys($scores), 0, $k, true) as $id) {
            $row = $rows[$id];
            $out[] = [
                'id' => (int) $row->id,
                'document_id' => (int) $row->document_id,
                'content' => (string) $row->content,
                'metadata' => $row->metadata ? json_decode($row->metadata, true) : null,
                'score' => round($scores[$id], 6),
            ];
        }

        return $out;
    }
}
