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
     * @return list<array{id:int,document_id:int,content:string,metadata:?array,score:float}>
     */
    public function retrieve(int $tenantId, string $query, int $k = 6): array
    {
        $k = max(1, min($k, 50));
        $pool = $k * 3;

        // --- semantic (degrades to lexical-only if embeddings are unavailable) ---
        $semantic = [];
        try {
            $qvec = $this->gemini->embedQuery($query);
            if ($qvec !== []) {
                $literal = '['.implode(',', $qvec).']';
                $semantic = DB::select(
                    'select id, document_id, content, metadata,
                            1 - (embedding <=> ?::vector) as score
                     from chunks
                     where tenant_id = ? and embedding is not null
                     order by embedding <=> ?::vector
                     limit '.(int) $pool,
                    [$literal, $tenantId, $literal]
                );
            }
        } catch (\Throwable $e) {
            report($e); // keep going with lexical results only
        }

        // --- lexical (BM25-ish) ---
        $lexical = DB::select(
            "select id, document_id, content, metadata,
                    ts_rank(content_tsv, plainto_tsquery('simple', ?)) as score
             from chunks
             where tenant_id = ? and content_tsv @@ plainto_tsquery('simple', ?)
             order by score desc
             limit ".(int) $pool,
            [$query, $tenantId, $query]
        );

        return $this->fuse([$semantic, $lexical], $k);
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
