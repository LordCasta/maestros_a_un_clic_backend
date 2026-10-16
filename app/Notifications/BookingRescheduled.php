<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * HU012: aviso a la otra parte de que la reserva cambió de hora. Vuelve a quedar pendiente
 * de que el profesional acepte el nuevo horario.
 */
class BookingRescheduled extends Notification
{
    public function __construct(public Booking $booking, public User $by, public Carbon $previousStartsAt) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.rescheduled';
    }

    public function broadcastType(): string
    {
        return 'booking.rescheduled';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing('service');

        $before = $this->previousStartsAt->copy()->setTimezone(config('app.timezone'))->locale('es')->isoFormat('D [de] MMMM, h:mm a');
        $now = BookingNotificationDate::format($this->booking);
        $next = $notifiable->getKey() === $this->booking->professional_id
            ? ' Revisa la solicitud para aceptar el nuevo horario.'
            : ' El profesional debe aceptar el nuevo horario.';

        return [
            'booking_id' => $this->booking->id,
            'title' => 'Reserva reprogramada',
            'body' => "{$this->by->name} movió «{$this->booking->service->title}» del {$before} al {$now}.".$next,
        ];
    }
}
