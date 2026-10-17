<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * HU011: mensaje nuevo en el chat de una reserva, al instante para quien lo tenga abierto.
 */
class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('booking.'.$this->message->booking_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * El mismo MessageResource que la API.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new MessageResource($this->message->loadMissing('sender')))->resolve();
    }
}
