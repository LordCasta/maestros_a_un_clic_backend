<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * HU012: aviso a la otra parte de que la reserva se canceló, con el motivo.
 */
class BookingCancelled extends Notification
{
    public function __construct(public Booking $booking, public User $by, public string $reason) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.cancelled';
    }

    public function broadcastType(): string
    {
        return 'booking.cancelled';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing('service');
        $who = $this->by->isAdmin() ? 'El equipo de Maestros a un clic' : $this->by->name;

        return [
            'booking_id' => $this->booking->id,
            'title' => 'Reserva cancelada',
            'body' => "{$who} canceló «{$this->booking->service->title}» del "
                .BookingNotificationDate::format($this->booking).". Motivo: {$this->reason}",
        ];
    }
}
