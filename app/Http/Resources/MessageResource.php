<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un mensaje del chat (HU011). El adjunto se entrega con una URL firmada que vence (disco privado).
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /** Minutos de validez de la URL del adjunto. */
    private const ATTACHMENT_URL_MINUTES = 60;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'sender' => new UserSummaryResource($this->whenLoaded('sender')),
            'body' => $this->body,
            'attachment_url' => $this->attachment_path
                ? app(UploadService::class)->temporaryPrivateUrl($this->attachment_path, self::ATTACHMENT_URL_MINUTES)
                : null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
