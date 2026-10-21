<?php

namespace App\Http\Resources;

use App\Models\AdminAlert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HU045: recurso para alertas del sistema hacia administradores.
 *
 * @mixin AdminAlert
 */
class AdminAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'type' => $this->type,
            'data' => $this->data,
            'resolver' => new UserSummaryResource($this->whenLoaded('resolver')),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
