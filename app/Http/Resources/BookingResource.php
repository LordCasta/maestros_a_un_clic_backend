<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'service' => new ProfessionalServiceResource($this->whenLoaded('service')),
            'client' => new UserSummaryResource($this->whenLoaded('client')),
            'professional' => new UserSummaryResource($this->whenLoaded('professional')),
            'description' => $this->description,
            'address' => $this->address,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'agreed_price' => (float) $this->agreed_price,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
