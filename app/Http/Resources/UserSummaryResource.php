<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Datos públicos mínimos de un usuario, para mostrarlo dentro de otro recurso
 * (la contraparte de una reserva, el autor de una reseña, etc.).
 *
 * @mixin User
 */
class UserSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'rating' => [
                'average' => $this->rating_avg,
                'count' => $this->rating_count,
            ],
        ];
    }
}
