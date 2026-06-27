<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiConfig extends Model
{
    protected $table = 'ai_configs';

    protected $fillable = [
        'tenant_id', 'dataset_id', 'name', 'system_prompt', 'data_scope', 'enabled_tools', 'model_tier',
        'public_key', 'allowed_domains', 'widget_enabled',
    ];

    protected $casts = [
        'data_scope' => 'array',
        'enabled_tools' => 'array',
        'allowed_domains' => 'array',
        'widget_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $config) {
            $config->public_key ??= 'pk_gtuh_'.Str::random(32);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** Resolve the configured tier to a concrete Claude model id. */
    public function modelId(): string
    {
        $tiers = config('services.anthropic.tiers');

        return $tiers[$this->model_tier] ?? config('services.anthropic.model');
    }

    /**
     * All dataset ids this agent searches: its home dataset + any extra ones in data_scope.
     *
     * @return list<int>
     */
    public function datasetIds(): array
    {
        $ids = [(int) $this->dataset_id];
        foreach ((array) ($this->data_scope['dataset_ids'] ?? []) as $id) {
            $ids[] = (int) $id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** Is this browser Origin allowed to embed this chatbot? Empty allowlist = any domain. */
    public function isDomainAllowed(?string $origin): bool
    {
        $domains = array_filter(array_map('trim', $this->allowed_domains ?? []));

        if ($domains === []) {
            return true;
        }
        if (! $origin) {
            return false;
        }

        $host = strtolower(parse_url($origin, PHP_URL_HOST) ?: $origin);

        foreach ($domains as $domain) {
            $domain = strtolower(ltrim($domain, '.'));
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
