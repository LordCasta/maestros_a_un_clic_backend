<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

/**
 * HU005: aviso al usuario de que recibió una calificación tras finalizar el servicio.
 */
class ReviewReceived extends Notification
{
    public function __construct(public Review $review) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'review.received';
    }

    public function broadcastType(): string
    {
        return 'review.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->review->loadMissing(['reviewer', 'booking.service']);

        $name = $this->review->reviewer->name;
        $stars = $this->review->rating === 1 ? '1 estrella' : "{$this->review->rating} estrellas";

        return [
            'booking_id' => $this->review->booking_id,
            'title' => 'Nueva calificación recibida',
            'body' => "{$name} te calificó con {$stars} por el servicio «{$this->review->booking->service->title}».",
        ];
    }
}
