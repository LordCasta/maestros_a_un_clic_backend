<?php

namespace App\Notifications;

use App\Models\Booking;

/**
 * Fecha de una reserva en los textos de las notificaciones: "17 de octubre, 9:00 a. m.".
 */
final class BookingNotificationDate
{
    public static function format(Booking $booking): string
    {
        return $booking->starts_at
            ->copy()
            ->setTimezone(config('app.timezone'))
            ->locale('es')
            ->isoFormat('D [de] MMMM, h:mm a');
    }
}
