<?php

namespace App\Models;

use App\Enums\CommuneType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Commune extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'type' => CommuneType::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function neighbors(): BelongsToMany
    {
        return $this->belongsToMany(Commune::class, 'commune_neighbors', 'commune_id', 'neighbor_id');
    }
}
