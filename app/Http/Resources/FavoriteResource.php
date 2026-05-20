<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'professional' => new ProfessionalResource($this->whenLoaded('professional')),
            'created_at' => $this->created_at,
        ];
    }
}

