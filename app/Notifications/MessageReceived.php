<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * HU011, HU018: aviso de mensaje nuevo en el chat de una reserva.
 */
class MessageReceived extends Notification
{
    public function __construct(public Message $message) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'message.received';
    }

    public function broadcastType(): string
    {
        return 'message.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing('sender');
        $preview = $this->message->body !== null
            ? Str::limit($this->message->body, 80)
            : 'Te envió una imagen.';

        return [
            'booking_id' => $this->message->booking_id,
            'title' => "Mensaje de {$this->message->sender->name}",
            'body' => $preview,
        ];
    }
}
