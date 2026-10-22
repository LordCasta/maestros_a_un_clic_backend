<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ModerationService $moderation) {}

    /**
     * HU024: reportar a un usuario por mal comportamiento, fraude o incumplimiento.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->moderation->report($request->user(), $request->validated());

        return $this->created(new ReportResource($report), 'Reporte enviado correctamente.');
    }
}
