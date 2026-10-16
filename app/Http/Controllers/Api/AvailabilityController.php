<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvailabilityBlockRequest;
use App\Http\Requests\UpdateAvailabilityRulesRequest;
use App\Http\Resources\AvailabilityBlockResource;
use App\Http\Resources\AvailabilityRuleResource;
use App\Models\AvailabilityBlock;
use App\Models\ProfessionalProfile;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU003: el profesional administra su agenda (horario semanal, descanso y bloqueos).
 */
class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function show(Request $request): JsonResponse
    {
        return $this->ok($this->agenda($request->user()->professionalProfile));
    }

    public function updateRules(UpdateAvailabilityRulesRequest $request): JsonResponse
    {
        $profile = $request->user()->professionalProfile;

        $this->availability->replaceRules($profile, $request->validated('rules'), $request->integer('buffer_minutes'));

        return $this->ok($this->agenda($profile->refresh()), 'Horario guardado.');
    }

    public function storeBlock(StoreAvailabilityBlockRequest $request): JsonResponse
    {
        $block = $this->availability->addBlock($request->user()->professionalProfile, $request->validated());

        return $this->created(new AvailabilityBlockResource($block), 'Horas bloqueadas.');
    }

    public function destroyBlock(AvailabilityBlock $block): JsonResponse
    {
        $this->authorize('delete', $block);

        $this->availability->removeBlock($block);

        return $this->ok(null, 'Bloqueo eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function agenda(ProfessionalProfile $profile): array
    {
        return [
            'buffer_minutes' => $profile->buffer_minutes,
            'rules' => AvailabilityRuleResource::collection(
                $profile->availabilityRules()->orderBy('weekday')->orderBy('start_time')->get(),
            ),
            // Solo los que aún no terminan: los pasados no afectan la agenda.
            'blocks' => AvailabilityBlockResource::collection(
                $profile->availabilityBlocks()->where('ends_at', '>', now())->orderBy('starts_at')->get(),
            ),
        ];
    }
}
