<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyContact extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'phone',
        'email',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
