<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListSlotsRequest;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProfessionalSlotController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * HU025: horas en las que se puede reservar un servicio, según el horario semanal,
     * los bloqueos y las reservas del profesional. Lo usa el formulario de reserva (HU004).
     */
    public function index(ListSlotsRequest $request, int $id): JsonResponse
    {
        $professional = User::publiclyListed()->with('professionalProfile')->findOrFail($id);

        $service = $professional->professionalProfile->services()->active()->find($request->integer('service_id'));

        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => 'Este servicio no pertenece al profesional o no está disponible.',
            ]);
        }

        $date = Carbon::createFromFormat('Y-m-d', $request->validated('date'), config('app.timezone'))->startOfDay();

        return $this->ok([
            'date' => $date->toDateString(),
            'service_id' => $service->id,
            'duration_minutes' => $service->estimated_duration_minutes,
            'slots' => array_map(fn (array $slot) => [
                'starts_at' => $slot['starts_at']->toIso8601String(),
                'ends_at' => $slot['ends_at']->toIso8601String(),
            ], $this->availability->slots($service, $date)),
        ]);
    }
}
