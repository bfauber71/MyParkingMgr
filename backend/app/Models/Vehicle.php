<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use HasUuids;

    protected $fillable = [
        'property',
        'tag_number',
        'plate_number',
        'state',
        'make',
        'model',
        'color',
        'year',
        'apt_number',
        'owner_name',
        'owner_phone',
        'owner_email',
        'reserved_space',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function propertyRelation(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property', 'name');
    }

    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(tag_number, plate_number, make, model, owner_name, apt_number) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerm]
        )->orWhere(function($q) use ($searchTerm) {
            $q->where('state', 'LIKE', "%{$searchTerm}%")
              ->orWhere('color', 'LIKE', "%{$searchTerm}%")
              ->orWhere('year', 'LIKE', "%{$searchTerm}%")
              ->orWhere('owner_phone', 'LIKE', "%{$searchTerm}%")
              ->orWhere('owner_email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('reserved_space', 'LIKE', "%{$searchTerm}%");
        });
    }

    public function scopeFilterByProperties(Builder $query, array $propertyNames): Builder
    {
        if (empty($propertyNames)) {
            return $query;
        }

        return $query->whereIn('property', $propertyNames);
    }
}
