<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU043: un mensaje se eliminó; desaparece del chat de las dos partes.
 */
class MessageDeleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $messageId, public int $bookingId) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('booking.'.$this->bookingId);
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    /**
     * @return array{id: int, booking_id: int}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->messageId, 'booking_id' => $this->bookingId];
    }
}
