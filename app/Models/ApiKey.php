<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['tenant_id', 'label', 'prefix', 'key_hash', 'last_used_at', 'revoked_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Issue a new key for a tenant.
     *
     * @return array{0: self, 1: string} [model, plaintext secret shown once]
     */
    public static function issue(Tenant $tenant, ?string $label = null): array
    {
        $secret = 'gtuh_'.Str::random(40);

        $key = static::create([
            'tenant_id' => $tenant->id,
            'label' => $label,
            'prefix' => substr($secret, 0, 12),
            'key_hash' => hash('sha256', $secret),
        ]);

        return [$key, $secret];
    }

    /** Look up an active key by its plaintext secret. */
    public static function findActiveBySecret(string $secret): ?self
    {
        return static::query()
            ->whereNull('revoked_at')
            ->where('key_hash', hash('sha256', $secret))
            ->first();
    }
}
