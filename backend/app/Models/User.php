<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasUuids;

    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function assignedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'user_assigned_properties')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function canAccessProperty(string $propertyName): bool
    {
        if ($this->isAdmin() || $this->isOperator()) {
            return true;
        }

        return $this->assignedProperties()->where('name', $propertyName)->exists();
    }

    public function getAccessiblePropertyNames(): array
    {
        if ($this->isAdmin() || $this->isOperator()) {
            return Property::pluck('name')->toArray();
        }

        return $this->assignedProperties()->pluck('name')->toArray();
    }
}
