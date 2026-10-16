<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * HU036: aviso al profesional de que el cliente confirmó la reserva.
 */
class BookingConfirmed extends Notification
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
        return 'booking.confirmed';
    }

    public function broadcastType(): string
    {
        return 'booking.confirmed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['client', 'service']);

        return [
            'booking_id' => $this->booking->id,
            'title' => 'Reserva confirmada',
            'body' => "{$this->booking->client->name} confirmó «{$this->booking->service->title}» para el "
                .BookingNotificationDate::format($this->booking).'.',
        ];
    }
}
