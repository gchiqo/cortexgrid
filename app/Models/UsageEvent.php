<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    public $timestamps = false; // table has only created_at (DB default)

    protected $fillable = ['tenant_id', 'api_key_id', 'kind', 'qty', 'cost'];

    protected $casts = [
        'cost' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function record(int $tenantId, string $kind, int $qty = 1, ?int $apiKeyId = null, ?float $cost = null): void
    {
        static::create([
            'tenant_id' => $tenantId,
            'api_key_id' => $apiKeyId,
            'kind' => $kind,
            'qty' => $qty,
            'cost' => $cost,
        ]);
    }
}
