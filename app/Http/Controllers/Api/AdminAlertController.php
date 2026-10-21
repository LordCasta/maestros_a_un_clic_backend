<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAlertResource;
use App\Models\AdminAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    /**
     * HU045: bandeja de alertas del sistema (bajas calificaciones, reportes, etc.).
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status'); // 'resolved', 'pending'

        $paginator = AdminAlert::query()
            ->with(['user.commune', 'resolver.commune'])
            ->when($status === 'resolved', fn (Builder $q) => $q->whereNotNull('resolved_at'))
            ->when($status === 'pending', fn (Builder $q) => $q->whereNull('resolved_at'))
            ->latest()
            ->paginate(15);

        return $this->paginated(AdminAlertResource::collection($paginator), $paginator);
    }

    /**
     * HU045: marcar una alerta como resuelta.
     */
    public function resolve(Request $request, AdminAlert $alert): JsonResponse
    {
        $alert->update([
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $alert->load(['user.commune', 'resolver.commune']);

        return $this->ok(new AdminAlertResource($alert), 'Alerta marcada como resuelta.');
    }
}
