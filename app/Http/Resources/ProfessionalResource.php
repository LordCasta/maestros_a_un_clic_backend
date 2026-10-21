<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Perfil público de un profesional. No expone email, teléfono ni dirección.
 * Espera el User con `professionalProfile` cargado; servicios y portafolio
 * solo se incluyen si se cargaron (en el detalle, no en el listado).
 *
 * @mixin User
 */
class ProfessionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->professionalProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'is_verified' => $this->isVerified(),
            'rating' => [
                'average' => $this->rating_avg,
                'count' => $this->rating_count,
            ],
            'description' => $profile?->description,
            'experience_years' => $profile?->experience_years,
            'hourly_rate' => $profile?->hourly_rate !== null ? (float) $profile->hourly_rate : null,
            'distance_km' => isset($this->distance_km) ? (float) round((float) $this->distance_km, 1) : null,
            'next_available_at' => $this->next_available_at ?? ($profile ? app(AvailabilityService::class)->nextAvailableSlot($profile) : null),
            'is_suggested' => $this->when(isset($this->is_suggested), (bool) $this->is_suggested),
            'categories' => $this->when(
                (bool) $profile?->relationLoaded('categories'),
                fn () => CategoryResource::collection($profile->categories),
            ),
            'services' => $this->when(
                (bool) $profile?->relationLoaded('services'),
                fn () => ProfessionalServiceResource::collection($profile->services),
            ),
            'portfolio' => $this->when(
                (bool) $profile?->relationLoaded('portfolioItems'),
                fn () => PortfolioItemResource::collection($profile->portfolioItems),
            ),
        ];
    }
}
