<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro inmutable de cada cambio de estado de una reserva (auditoría, HU012, HU026).
 */
class BookingStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'previous_starts_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'previous_starts_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
