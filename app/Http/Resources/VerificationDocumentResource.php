<?php

namespace App\Http\Resources;

use App\Models\VerificationDocument;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VerificationDocument
 */
class VerificationDocumentResource extends JsonResource
{
    private const URL_MINUTES = 60;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value ?? $this->type,
            'title' => $this->title,
            'status' => $this->status->value ?? $this->status,
            'rejection_reason' => $this->rejection_reason,
            'file_url' => $this->file_path
                ? app(UploadService::class)->temporaryPrivateUrl($this->file_path, self::URL_MINUTES)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
