<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * HU004: aviso al profesional de una nueva solicitud. Queda en `notifications` (panel, HU018)
 * y llega al instante por el canal privado App.Models.User.{id}.
 */
class BookingRequested extends Notification
{
    public function __construct(public Booking $booking) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.requested';
    }

    public function broadcastType(): string
    {
        return 'booking.requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['client', 'service']);

        return [
            'booking_id' => $this->booking->id,
            'title' => 'Nueva solicitud de reserva',
            'body' => "{$this->booking->client->name} solicitó «{$this->booking->service->title}» para el "
                .BookingNotificationDate::format($this->booking).'.',
        ];
    }
}
