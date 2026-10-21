<?php

namespace App\Http\Controllers\Api;

use App\Enums\AlertType;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\AdminAlert;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * HU024: reportar a un usuario por mal comportamiento, fraude o incumplimiento.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $request->validated('reported_user_id'),
            'booking_id' => $request->validated('booking_id'),
            'reason' => $request->validated('reason'),
            'description' => $request->validated('description'),
            'status' => ReportStatus::Open,
        ]);

        // Generar alerta para administradores
        AdminAlert::create([
            'user_id' => $report->reported_user_id,
            'type' => AlertType::Report,
            'data' => [
                'report_id' => $report->id,
                'reporter_id' => $report->reporter_id,
                'reason' => $report->reason,
            ],
        ]);

        $report->load(['reporter.commune', 'reportedUser.commune']);

        return $this->created(new ReportResource($report), 'Reporte enviado correctamente.');
    }
}
