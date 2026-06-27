<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = ['tenant_id', 'ai_config_id', 'conversation_id', 'name', 'email', 'phone', 'message'];

    public function config(): BelongsTo
    {
        return $this->belongsTo(AiConfig::class, 'ai_config_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
