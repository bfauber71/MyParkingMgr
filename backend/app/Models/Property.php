<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(PropertyContact::class)->orderBy('position');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'property', 'name');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_assigned_properties')
            ->withTimestamps();
    }
}
