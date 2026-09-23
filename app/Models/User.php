<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'phone', 'password', 'is_admin', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
            'phone_verified_at' => 'datetime',
            'phone_verification_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
    public function orders(): HasMany
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Modules\Customers\Models\CustomerAddress::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        if (! $this->is_admin || ! Schema::hasTable('roles')) return (bool) $this->is_admin;
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        return $roles->contains('slug', 'super-admin');
    }

    public function permissionNames(): array
    {
        if (! $this->is_admin) return [];
        if ($this->isSuperAdmin()) return ['*'];
        return $this->roles()->with('permissions:id,name')->get()->pluck('permissions')->flatten()->pluck('name')->unique()->values()->all();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_admin || ! ($this->is_active ?? true)) return false;
        $permissions = $this->permissionNames();
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
