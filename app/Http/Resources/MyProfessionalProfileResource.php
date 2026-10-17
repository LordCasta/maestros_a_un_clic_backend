<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HU009: el perfil del profesional autenticado para editarlo. A diferencia de
 * ProfessionalResource (público), incluye su dirección y coordenadas.
 *
 * @mixin User
 */
class MyProfessionalProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->professionalProfile;

        return [
            'description' => $profile->description,
            'experience_years' => $profile->experience_years,
            'hourly_rate' => $profile->hourly_rate !== null ? (float) $profile->hourly_rate : null,
            'service_radius_km' => $profile->service_radius_km,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'categories' => CategoryResource::collection($profile->categories),
            'verification_status' => $this->verification_status,
        ];
    }
}
