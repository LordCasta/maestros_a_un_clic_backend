<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfessionalServiceRequest;
use App\Http\Requests\UpdateProfessionalServiceRequest;
use App\Http\Resources\ProfessionalServiceResource;
use App\Models\ProfessionalService;
use App\Services\ProfessionalProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU009, HU047: servicios del profesional autenticado, incluidos los inactivos.
 */
class ProfessionalServiceController extends Controller
{
    public function __construct(private readonly ProfessionalProfileService $profiles) {}

    public function index(Request $request): JsonResponse
    {
        $services = $request->user()->professionalProfile->services()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        return $this->ok(ProfessionalServiceResource::collection($services));
    }

    public function store(StoreProfessionalServiceRequest $request): JsonResponse
    {
        $service = $this->profiles->createService($request->user()->professionalProfile, $request->validated());

        return $this->created(new ProfessionalServiceResource($service->load('category')), 'Servicio creado.');
    }

    public function update(UpdateProfessionalServiceRequest $request, ProfessionalService $service): JsonResponse
    {
        $this->authorize('update', $service);

        $service = $this->profiles->updateService($service, $request->validated());

        return $this->ok(new ProfessionalServiceResource($service->load('category')), 'Servicio actualizado.');
    }

    public function destroy(ProfessionalService $service): JsonResponse
    {
        $this->authorize('delete', $service);

        $this->profiles->deleteService($service);

        return $this->ok(null, 'Servicio eliminado.');
    }
}
