<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * HU018: una notificación del usuario. Misma forma que la que llega por el WebSocket
 * (id, type y los datos de la notificación), más read_at y created_at.
 *
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'booking_id' => $this->data['booking_id'] ?? null,
            'title' => $this->data['title'] ?? '',
            'body' => $this->data['body'] ?? '',
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
