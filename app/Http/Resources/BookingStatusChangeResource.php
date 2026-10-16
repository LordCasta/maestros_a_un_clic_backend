<?php

namespace App\Http\Resources;

use App\Models\BookingStatusChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un paso del historial de una reserva (HU026).
 *
 * @mixin BookingStatusChange
 */
class BookingStatusChangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status->value,
            'reason' => $this->reason,
            // Solo al reprogramar (HU012): la fecha que tenía antes.
            'previous_starts_at' => $this->previous_starts_at?->toIso8601String(),
            'changed_by' => $this->changedBy
                ? ['id' => $this->changedBy->id, 'name' => $this->changedBy->name]
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
