<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HU024: recurso para reportes de usuarios.
 *
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reporter' => new UserSummaryResource($this->whenLoaded('reporter')),
            'reported_user' => new UserSummaryResource($this->whenLoaded('reportedUser')),
            'booking_id' => $this->booking_id,
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'resolver' => new UserSummaryResource($this->whenLoaded('resolver')),
            'resolution_notes' => $this->resolution_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
