<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Platform-wide key/value settings, edited from the admin panel.
 *
 * Reads fall back to config() so a fresh install works from .env alone, and
 * so the app still boots if the table is missing (e.g. before migrating).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'encrypted'];

    private const CACHE_KEY = 'settings.all';

    /** @return array<string,string> every stored setting, cached */
    public static function all_(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return self::query()->get()->mapWithKeys(
                    fn (self $s) => [$s->key => $s->value]
                )->all();
            } catch (Throwable) {
                return [];   // table not migrated yet
            }
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $v = self::all_()[$key] ?? null;

        return ($v === null || $v === '') ? $default : $v;
    }

    /** @param array<string,mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                self::query()->whereKey($key)->delete();

                continue;
            }
            self::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
