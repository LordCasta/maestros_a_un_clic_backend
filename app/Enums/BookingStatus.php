<?php

namespace App\Enums;

/**
 * Estados de una reserva. Ver la máquina de estados en docs/modelo-de-datos.md §3.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Estados que ocupan un bloque en la agenda del profesional.
     *
     * @return list<self>
     */
    public static function occupyingSchedule(): array
    {
        return [self::Pending, self::Accepted, self::Confirmed, self::InProgress];
    }

    /**
     * Estados a los que se puede pasar desde el estado actual.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Accepted, self::Rejected, self::Cancelled],
            self::Accepted => [self::Confirmed, self::Pending, self::Cancelled],
            self::Confirmed => [self::InProgress, self::Pending, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Rejected, self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function occupiesSchedule(): bool
    {
        return in_array($this, self::occupyingSchedule(), true);
    }
}
