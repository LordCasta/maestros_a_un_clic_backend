<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    /**
     * HU024: bandeja de reportes de usuarios para moderadores/administradores.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $paginator = Report::query()
            ->with(['reporter.commune', 'reportedUser.commune', 'resolver.commune'])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return $this->paginated(ReportResource::collection($paginator), $paginator);
    }

    /**
     * HU024: resolver o desestimar un reporte de usuario con notas y bloqueo opcional.
     */
    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $report->status = $request->validated('status');
        $report->resolution_notes = $request->validated('resolution_notes');
        $report->resolved_by = $request->user()->id;
        $report->save();

        if ($request->boolean('block_user')) {
            $reportedUser = $report->reportedUser;
            if ($reportedUser && ! $reportedUser->isAdmin()) {
                $reportedUser->update([
                    'blocked_at' => now(),
                    'blocked_reason' => "Reporte #{$report->id}: {$request->validated('resolution_notes')}",
                    'blocked_by' => $request->user()->id,
                ]);
                $reportedUser->tokens()->delete();
            }
        }

        $report->load(['reporter.commune', 'reportedUser.commune', 'resolver.commune']);

        return $this->ok(new ReportResource($report), 'Reporte actualizado correctamente.');
    }
}
