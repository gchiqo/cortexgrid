<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Admin of their own tenant. Every registered user gets this. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Operator of the whole install — may edit platform settings such as the
     * model provider and its API keys.
     *
     * Distinct from isAdmin(): ProvisionTenant makes every signup an admin of
     * their own tenant, which must not expose the operator's credentials.
     * Listed in PLATFORM_ADMINS, or the first account if that list is empty.
     */
    public function isPlatformAdmin(): bool
    {
        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) config('app.platform_admins'))
        ));

        if ($allowed !== []) {
            return in_array(mb_strtolower($this->email), array_map('mb_strtolower', $allowed), true);
        }

        return $this->id === static::query()->min('id');
    }
}
