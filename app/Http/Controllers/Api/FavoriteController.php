<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU034, HU035. Solo clientes (middleware role:client). Sin Service: no hay reglas
 * de negocio más allá de una consulta por usuario.
 */
class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with(['professional.commune', 'professional.professionalProfile.categories'])
            ->latest()
            ->get();

        return $this->ok(FavoriteResource::collection($favorites));
    }

    public function store(Request $request, int $professionalId): JsonResponse
    {
        $professional = User::publiclyListed()->findOrFail($professionalId);

        $favorite = Favorite::firstOrCreate([
            'client_id' => $request->user()->id,
            'professional_id' => $professional->id,
        ]);

        $favorite->load(['professional.commune', 'professional.professionalProfile.categories']);

        return $this->created(new FavoriteResource($favorite), 'Agregado a favoritos.');
    }

    public function destroy(Request $request, int $professionalId): JsonResponse
    {
        $request->user()->favorites()->where('professional_id', $professionalId)->delete();

        return $this->ok(message: 'Eliminado de favoritos.');
    }
}
