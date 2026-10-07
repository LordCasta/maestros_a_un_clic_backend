<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de la cuenta autenticada (login, registro, /auth/me). Incluye datos privados:
 * solo se devuelve al propio usuario o a un administrador.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'verification_status' => $this->verification_status,
            'rating' => [
                'average' => $this->rating_avg,
                'count' => $this->rating_count,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
