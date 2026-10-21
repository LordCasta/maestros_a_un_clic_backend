<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * HU038: recurso de usuario para la bandeja de administración (incluye estado de bloqueo).
 *
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'role' => $this->role,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'address' => $this->address,
            'verification_status' => $this->verification_status,
            'rating' => [
                'average' => $this->rating_avg,
                'count' => $this->rating_count,
            ],
            'is_blocked' => $this->blocked_at !== null,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'blocked_reason' => $this->blocked_reason,
            'blocked_by' => new UserSummaryResource($this->whenLoaded('blockedBy')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
