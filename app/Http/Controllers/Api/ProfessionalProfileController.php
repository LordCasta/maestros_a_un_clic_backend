<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncProfessionalCategoriesRequest;
use App\Http\Requests\UpdateProfessionalProfileRequest;
use App\Http\Resources\MyProfessionalProfileResource;
use App\Models\User;
use App\Services\ProfessionalProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU009: "Sobre mí" del profesional autenticado.
 */
class ProfessionalProfileController extends Controller
{
    public function __construct(private readonly ProfessionalProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return $this->ok($this->resource($request->user()));
    }

    public function update(UpdateProfessionalProfileRequest $request): JsonResponse
    {
        $this->profiles->updateProfile($request->user(), $request->validated());

        return $this->ok($this->resource($request->user()->refresh()), 'Perfil actualizado.');
    }

    public function categories(SyncProfessionalCategoriesRequest $request): JsonResponse
    {
        $this->profiles->syncCategories($request->user()->professionalProfile, $request->validated('category_ids'));

        return $this->ok($this->resource($request->user()->refresh()), 'Especialidades actualizadas.');
    }

    private function resource(User $user): MyProfessionalProfileResource
    {
        return new MyProfessionalProfileResource($user->load(['commune', 'professionalProfile.categories']));
    }
}
