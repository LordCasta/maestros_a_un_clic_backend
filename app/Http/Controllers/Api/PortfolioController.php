<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioItemRequest;
use App\Http\Resources\PortfolioItemResource;
use App\Models\PortfolioItem;
use App\Services\ProfessionalProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU009: galería de trabajos realizados del profesional autenticado.
 */
class PortfolioController extends Controller
{
    public function __construct(private readonly ProfessionalProfileService $profiles) {}

    public function index(Request $request): JsonResponse
    {
        return $this->ok(PortfolioItemResource::collection(
            $request->user()->professionalProfile->portfolioItems()->latest('id')->get(),
        ));
    }

    public function store(StorePortfolioItemRequest $request): JsonResponse
    {
        $item = $this->profiles->addPortfolioItem(
            $request->user()->professionalProfile,
            $request->file('image'),
            $request->validated('description'),
        );

        return $this->created(new PortfolioItemResource($item), 'Foto agregada al portafolio.');
    }

    public function destroy(PortfolioItem $item): JsonResponse
    {
        $this->authorize('delete', $item);

        $this->profiles->removePortfolioItem($item);

        return $this->ok(null, 'Foto eliminada.');
    }
}
