<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * HU044: vista del perfil del cliente por parte del profesional.
 *
 * @mixin User
 */
class ClientProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'rating' => [
                'average' => $this->rating_avg,
                'count' => $this->rating_count,
            ],
            'completed_bookings_count' => $this->bookings_as_client_count ?? $this->bookingsAsClient()->where('status', 'completed')->count(),
            'member_since' => $this->created_at?->toIso8601String(),
        ];
    }
}
