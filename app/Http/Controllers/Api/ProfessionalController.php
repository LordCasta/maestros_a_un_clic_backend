<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProfessionalsRequest;
use App\Http\Resources\ProfessionalResource;
use App\Models\User;
use App\Services\ProfessionalSearchService;
use Illuminate\Http\JsonResponse;

class ProfessionalController extends Controller
{
    public function index(SearchProfessionalsRequest $request, ProfessionalSearchService $search): JsonResponse
    {
        $paginator = $search->search($request->validated(), (int) $request->input('per_page', 15));

        return $this->paginated(ProfessionalResource::collection($paginator), $paginator);
    }

    public function show(int $id): JsonResponse
    {
        $professional = User::publiclyListed()
            ->with([
                'commune',
                'professionalProfile.categories',
                'professionalProfile.services' => fn ($q) => $q->active()->with('category'),
                'professionalProfile.portfolioItems',
            ])
            ->findOrFail($id);

        return $this->ok(new ProfessionalResource($professional));
    }
}
