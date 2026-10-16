<?php

namespace App\Events;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * HU026: cambió el estado de una reserva. Quien tenga abierto su detalle lo ve al instante.
 * Lo emite BookingService::transition() en cada cambio de estado.
 */
class BookingStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('booking.'.$this->booking->id);
    }

    public function broadcastAs(): string
    {
        return 'booking.status-changed';
    }

    /**
     * El mismo BookingResource que la API, para que el frontend reemplace la reserva en caché.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new BookingResource($this->booking->load(['service.category', 'client', 'professional', 'commune'])))->resolve();
    }
}
