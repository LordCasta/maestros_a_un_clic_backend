<?php

namespace App\Http\Resources;

use App\Models\ProfessionalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Servicio que ofrece un profesional.
 *
 * @mixin ProfessionalService
 */
class ProfessionalServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'price_type' => $this->price_type,
            'price' => (float) $this->price,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'is_active' => $this->is_active,
        ];
    }
}
