<?php

namespace App\Broadcasting;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Canal de presencia `presence-booking.{id}`: chat de la reserva (HU011) y estado
 * en línea de la contraparte (HU041). Solo entran el cliente y el profesional.
 */
class BookingChannel
{
    /**
     * @return array{id: int, name: string, avatar_url: string|null}|false
     */
    public function join(User $user, Booking $booking): array|false
    {
        if (! $booking->involves($user)) {
            return false;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
        ];
    }
}
