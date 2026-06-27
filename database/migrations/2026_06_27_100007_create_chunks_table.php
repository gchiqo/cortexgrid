<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Embedding dimension must match the Gemini embedding model (text-embedding-004 = 768).
        $dim = (int) env('EMBEDDING_DIM', 768);

        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // pgvector column (filled asynchronously by the embedding job).
        DB::statement("ALTER TABLE chunks ADD COLUMN embedding vector({$dim})");

        // Generated tsvector for lexical / BM25-style search.
        // 'simple' = no stemming dictionary — the safe choice for Georgian.
        DB::statement(
            "ALTER TABLE chunks ADD COLUMN content_tsv tsvector "
            . "GENERATED ALWAYS AS (to_tsvector('simple', coalesce(content, ''))) STORED"
        );

        // Semantic index (cosine) + lexical index.
        DB::statement('CREATE INDEX chunks_embedding_hnsw ON chunks USING hnsw (embedding vector_cosine_ops)');
        DB::statement('CREATE INDEX chunks_content_tsv_gin ON chunks USING gin (content_tsv)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
