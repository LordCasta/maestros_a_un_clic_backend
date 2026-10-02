<?php

namespace App\Models;

use App\Enums\PriceType;
use Database\Factories\ProfessionalServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfessionalService extends Model
{
    /** @use HasFactory<ProfessionalServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'professional_profile_id',
        'category_id',
        'title',
        'description',
        'price_type',
        'price',
        'estimated_duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'price' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function professionalProfile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
