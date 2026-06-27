<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfig extends Model
{
    protected $table = 'ai_configs';

    protected $fillable = ['tenant_id', 'name', 'system_prompt', 'data_scope', 'enabled_tools', 'model_tier'];

    protected $casts = [
        'data_scope' => 'array',
        'enabled_tools' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Resolve the configured tier to a concrete Claude model id. */
    public function modelId(): string
    {
        $tiers = config('services.anthropic.tiers');

        return $tiers[$this->model_tier] ?? config('services.anthropic.model');
    }
}
