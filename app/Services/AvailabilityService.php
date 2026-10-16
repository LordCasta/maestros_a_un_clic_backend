<?php

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\ProfessionalProfile;
use App\Models\ProfessionalService;
use Illuminate\Support\Carbon;

/**
 * Disponibilidad real de un profesional (HU003, HU025): su horario semanal (availability_rules),
 * sus bloqueos (availability_blocks) y las reservas que ocupan agenda, contando el tiempo de descanso.
 * Todas las horas se manejan en la zona de la app (config('app.timezone')).
 */
class AvailabilityService
{
    /** Cada cuántos minutos puede empezar un servicio dentro de una franja del horario. */
    public const SLOT_STEP_MINUTES = 30;

    /**
     * Horas libres de una fecha para un servicio: caben completas en una franja del horario,
     * no tocan bloqueos ni otras reservas (con su descanso) y todavía no han pasado.
     *
     * @return list<array{starts_at: Carbon, ends_at: Carbon}>
     */
    public function slots(ProfessionalService $service, Carbon $date): array
    {
        $profile = $service->professionalProfile;
        $duration = $service->estimated_duration_minutes;
        $day = $date->copy()->setTimezone(config('app.timezone'))->startOfDay();
        $busy = $this->busyIntervals($profile, $day, $day->copy()->endOfDay());
        $now = Carbon::now();
        $slots = [];

        foreach ($this->windows($profile, $day) as [$windowStart, $windowEnd]) {
            $start = $windowStart->copy();

            while ($start->copy()->addMinutes($duration)->lte($windowEnd)) {
                $end = $start->copy()->addMinutes($duration);

                if ($start->gt($now) && ! $this->overlapsAny($busy, $start, $end)) {
                    $slots[] = ['starts_at' => $start->copy(), 'ends_at' => $end];
                }

                $start->addMinutes(self::SLOT_STEP_MINUTES);
            }
        }

        return $slots;
    }

    /**
     * El profesional atiende en [$startsAt, $endsAt): cabe en una franja de su horario y no hay bloqueos.
     * Los cruces con otras reservas se revisan aparte (hasBookingConflict) porque responden 409.
     */
    public function withinSchedule(ProfessionalProfile $profile, Carbon $startsAt, Carbon $endsAt): bool
    {
        $startsAt = $startsAt->copy()->setTimezone(config('app.timezone'));

        $fitsWindow = collect($this->windows($profile, $startsAt->copy()->startOfDay()))
            ->contains(fn (array $window) => $startsAt->gte($window[0]) && $endsAt->lte($window[1]));

        return $fitsWindow && ! $profile->availabilityBlocks()
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    /**
     * Otra reserva activa del profesional se cruza con [$startsAt, $endsAt), contando su descanso.
     */
    public function hasBookingConflict(ProfessionalProfile $profile, Carbon $startsAt, Carbon $endsAt): bool
    {
        return Booking::where('professional_id', $profile->user_id)
            ->occupyingSchedule()
            ->overlapping(
                $startsAt->copy()->subMinutes($profile->buffer_minutes),
                $endsAt->copy()->addMinutes($profile->buffer_minutes),
            )
            ->exists();
    }

    /**
     * Franjas del horario semanal para ese día, como pares [inicio, fin].
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function windows(ProfessionalProfile $profile, Carbon $day): array
    {
        return $profile->availabilityRules()
            ->where('weekday', $day->dayOfWeek)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($rule) => [
                $day->copy()->setTimeFromTimeString($rule->start_time),
                $day->copy()->setTimeFromTimeString($rule->end_time),
            ])
            ->all();
    }

    /**
     * Intervalos ocupados del día: reservas activas ampliadas con el descanso, y bloqueos.
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function busyIntervals(ProfessionalProfile $profile, Carbon $from, Carbon $to): array
    {
        $buffer = $profile->buffer_minutes;

        $bookings = Booking::where('professional_id', $profile->user_id)
            ->occupyingSchedule()
            ->overlapping($from->copy()->subMinutes($buffer), $to->copy()->addMinutes($buffer))
            ->get(['starts_at', 'ends_at'])
            ->map(fn (Booking $booking) => [
                $booking->starts_at->copy()->subMinutes($buffer),
                $booking->ends_at->copy()->addMinutes($buffer),
            ]);

        $blocks = $profile->availabilityBlocks()
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->get(['starts_at', 'ends_at'])
            ->map(fn (AvailabilityBlock $block) => [$block->starts_at, $block->ends_at]);

        return $bookings->concat($blocks)->all();
    }

    /**
     * @param  list<array{0: Carbon, 1: Carbon}>  $intervals
     */
    private function overlapsAny(array $intervals, Carbon $startsAt, Carbon $endsAt): bool
    {
        foreach ($intervals as [$busyStart, $busyEnd]) {
            if ($startsAt->lt($busyEnd) && $endsAt->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }
}
