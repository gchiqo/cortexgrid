<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['source_id', 'tenant_id', 'dataset_id', 'external_id', 'title', 'raw_text', 'structured'];

    protected $casts = ['structured' => 'array'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }
}
