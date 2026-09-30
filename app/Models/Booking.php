<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'professional_id',
        'professional_service_id',
        'description',
        'address',
        'commune_id',
        'starts_at',
        'ends_at',
        'status',
        'agreed_price',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'agreed_price' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProfessionalService::class, 'professional_service_id');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(BookingStatusChange::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Reservas que ocupan agenda (ver BookingStatus::occupyingSchedule).
     */
    public function scopeOccupyingSchedule(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::occupyingSchedule());
    }

    /**
     * Reservas cuyo intervalo se cruza con [$startsAt, $endsAt).
     */
    public function scopeOverlapping(Builder $query, Carbon $startsAt, Carbon $endsAt): void
    {
        $query->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }

    public function involves(User $user): bool
    {
        return $user->id === $this->client_id || $user->id === $this->professional_id;
    }
}
