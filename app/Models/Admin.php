<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_super_admin',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string|array $roles): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission(string|array $permissions): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->whereIn('permissions.slug', $permissions))
            ->exists();
    }
}