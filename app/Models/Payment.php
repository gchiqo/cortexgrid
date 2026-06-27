<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['tenant_id', 'amount_gel', 'credits', 'status', 'gateway_response'];

    protected $casts = [
        'amount_gel' => 'decimal:2',
        'gateway_response' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
