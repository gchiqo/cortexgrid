<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['tenant_id', 'ai_config_id', 'visitor_id', 'title'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(AiConfig::class, 'ai_config_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
