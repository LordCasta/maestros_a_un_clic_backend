<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'professional_id' => $this->professional_id,
            'service_description' => $this->service_description,
            'scheduled_date' => $this->scheduled_date,
            'status' => $this->status,
            'total' => $this->total,
            'created_at' => $this->created_at,
        ];
    }
}

