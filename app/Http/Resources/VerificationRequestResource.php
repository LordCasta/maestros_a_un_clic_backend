<?php

namespace App\Http\Resources;

use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VerificationRequest
 */
class VerificationRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value ?? $this->status,
            'rejection_reason' => $this->rejection_reason,
            'background_check_status' => $this->background_check_status->value ?? $this->background_check_status,
            'background_check_notes' => $this->background_check_notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'reviewer' => new UserSummaryResource($this->whenLoaded('reviewer')),
            'documents' => VerificationDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
