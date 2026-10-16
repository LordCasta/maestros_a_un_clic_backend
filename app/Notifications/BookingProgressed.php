<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * HU027, HU028: aviso al cliente de que el profesional inició o finalizó el servicio.
 */
class BookingProgressed extends Notification
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

        $who = $this->booking->professional->name;
        $what = "«{$this->booking->service->title}»";

        return [
            'booking_id' => $this->booking->id,
            ...($this->completed()
                ? ['title' => 'Servicio finalizado', 'body' => "{$who} finalizó {$what}. Cuéntanos cómo te fue calificando el servicio."]
                : ['title' => 'Servicio en proceso', 'body' => "{$who} inició {$what}."]),
        ];
    }

    private function completed(): bool
    {
        return $this->booking->status === BookingStatus::Completed;
    }

    private function type(): string
    {
        return $this->completed() ? 'booking.completed' : 'booking.started';
    }
}
