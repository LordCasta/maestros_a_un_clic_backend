<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Enums\ReportStatus;
use App\Models\AdminAlert;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * HU024, HU038, HU045: reportes de usuarios, bloqueo de cuentas y alertas para el administrador.
 */
class ModerationService
{
    /**
     * HU024: registra el reporte y abre una alerta para los administradores.
     *
     * @param  array{reported_user_id: int, booking_id?: int|null, reason: string, description: string}  $data
     */
    public function report(User $reporter, array $data): Report
    {
        $report = DB::transaction(function () use ($reporter, $data) {
            $report = Report::create([
                'reporter_id' => $reporter->id,
                'reported_user_id' => $data['reported_user_id'],
                'booking_id' => $data['booking_id'] ?? null,
                'reason' => $data['reason'],
                'description' => $data['description'],
                'status' => ReportStatus::Open,
            ]);

            AdminAlert::create([
                'user_id' => $report->reported_user_id,
                'type' => AlertType::Report,
                'data' => [
                    'report_id' => $report->id,
                    'reporter_id' => $report->reporter_id,
                    'reason' => $report->reason,
                ],
            ]);

            return $report;
        });

        return $report->load(['reporter.commune', 'reportedUser.commune']);
    }

    /**
     * HU024: el administrador resuelve o desestima el reporte y, si lo pide, bloquea al reportado.
     *
     * @param  array{status: string, resolution_notes: string, block_user?: bool|null}  $data
     */
    public function resolveReport(Report $report, User $admin, array $data): Report
    {
        DB::transaction(function () use ($report, $admin, $data) {
            $report->update([
                'status' => $data['status'],
                'resolution_notes' => $data['resolution_notes'],
                'resolved_by' => $admin->id,
            ]);

            $reported = $report->reportedUser;

            // Un administrador nunca queda bloqueado por un reporte.
            if (($data['block_user'] ?? false) && $reported && ! $reported->isAdmin()) {
                $this->applyBlock($reported, $admin, "Reporte #{$report->id}: {$data['resolution_notes']}");
            }
        });

        return $report->load(['reporter.commune', 'reportedUser.commune', 'resolver.commune']);
    }

    /**
     * HU038: bloquea la cuenta y cierra sus sesiones.
     */
    public function block(User $user, User $admin, string $reason): User
    {
        abort_if($user->is($admin), 422, 'No puedes bloquear tu propia cuenta.');
        abort_if($user->isAdmin(), 403, 'No se puede bloquear a un administrador.');

        $this->applyBlock($user, $admin, $reason);

        return $user->load(['commune', 'blockedBy']);
    }

    /**
     * HU038
     */
    public function unblock(User $user): User
    {
        $user->update([
            'blocked_at' => null,
            'blocked_reason' => null,
            'blocked_by' => null,
        ]);

        return $user->load(['commune', 'blockedBy']);
    }

    /**
     * HU045
     */
    public function resolveAlert(AdminAlert $alert, User $admin): AdminAlert
    {
        $alert->update([
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        return $alert->load(['user.commune', 'resolver.commune']);
    }

    private function applyBlock(User $user, User $admin, string $reason): void
    {
        $user->update([
            'blocked_at' => now(),
            'blocked_reason' => $reason,
            'blocked_by' => $admin->id,
        ]);

        $user->tokens()->delete();
    }
}
