<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\Vector;

class Chunk extends Model
{
    protected $fillable = ['document_id', 'tenant_id', 'dataset_id', 'content', 'metadata', 'embedding'];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => Vector::class,
    ];

    // content_tsv is a generated column — it is read-only and not in $fillable,
    // so mass-assignment / saves never touch it.

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
