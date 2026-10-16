<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * HU004: aviso al cliente de que el profesional aceptó o rechazó su solicitud.
 */
class BookingAnswered extends Notification
{
    public function __construct(public Booking $booking, public ?string $reason = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type();
    }

    public function broadcastType(): string
    {
        return $this->type();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing(['professional', 'service']);

        $accepted = $this->booking->status === BookingStatus::Accepted;
        $who = $this->booking->professional->name;
        $what = "«{$this->booking->service->title}» del ".BookingNotificationDate::format($this->booking);

        return [
            'booking_id' => $this->booking->id,
            'title' => $accepted ? 'Solicitud aceptada' : 'Solicitud rechazada',
            'body' => $accepted
                ? "{$who} aceptó {$what}. Confírmala para dejarla reservada."
                : "{$who} no puede atender {$what}.".($this->reason ? " Motivo: {$this->reason}" : ''),
        ];
    }

    private function type(): string
    {
        return $this->booking->status === BookingStatus::Accepted ? 'booking.accepted' : 'booking.rejected';
    }
}
