<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de las reservas. Todo cambio de estado pasa por transition()
 * para respetar la máquina de estados (BookingStatus) y dejar auditoría.
 */
class BookingService
{
    /**
     * @param  array<string, mixed>  $data  Datos validados por StoreBookingRequest.
     */
    public function create(User $client, array $data): Booking
    {
        $service = ProfessionalService::with('professionalProfile.user')->findOrFail($data['professional_service_id']);
        $professional = $service->professionalProfile->user;

        if (! $service->is_active || ! User::publiclyListed()->whereKey($professional->id)->exists()) {
            throw ValidationException::withMessages([
                'professional_service_id' => 'Este servicio no está disponible para reservas.',
            ]);
        }

        // Normaliza a la zona de la app: Eloquent guarda la hora "de reloj" sin convertir.
        $startsAt = Carbon::parse($data['starts_at'])->setTimezone(config('app.timezone'));
        $endsAt = $startsAt->copy()->addMinutes($service->estimated_duration_minutes);

        $this->ensureSlotIsFree($professional, $startsAt, $endsAt, $service->professionalProfile->buffer_minutes);

        return DB::transaction(function () use ($client, $professional, $service, $data, $startsAt, $endsAt) {
            $booking = Booking::create([
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'professional_service_id' => $service->id,
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'commune_id' => $data['commune_id'] ?? $client->commune_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => BookingStatus::Pending,
                // Copia del precio vigente: si el profesional lo cambia después, la reserva no se altera.
                'agreed_price' => $service->price,
            ]);

            $booking->statusChanges()->create([
                'from_status' => null,
                'to_status' => BookingStatus::Pending,
                'changed_by' => $client->id,
            ]);

            return $booking;
        });
    }

    public function cancel(Booking $booking, User $by, string $reason): Booking
    {
        return $this->transition($booking, BookingStatus::Cancelled, $by, $reason);
    }

    /**
     * Cambia el estado validando la transición y registra el cambio en booking_status_changes.
     */
    public function transition(Booking $booking, BookingStatus $to, User $by, ?string $reason = null): Booking
    {
        abort_unless(
            $booking->status->canTransitionTo($to),
            409,
            "No se puede pasar una reserva de '{$booking->status->value}' a '{$to->value}'.",
        );

        return DB::transaction(function () use ($booking, $to, $by, $reason) {
            $booking->statusChanges()->create([
                'from_status' => $booking->status,
                'to_status' => $to,
                'changed_by' => $by->id,
                'reason' => $reason,
            ]);

            $booking->update(['status' => $to]);

            return $booking;
        });
    }

    /**
     * Evita que dos reservas activas del profesional se crucen, incluyendo su tiempo de descanso (HU003).
     * El módulo de agenda amplía esta validación con el horario semanal y los bloqueos.
     */
    private function ensureSlotIsFree(User $professional, Carbon $startsAt, Carbon $endsAt, int $bufferMinutes): void
    {
        $taken = Booking::where('professional_id', $professional->id)
            ->occupyingSchedule()
            ->overlapping($startsAt->copy()->subMinutes($bufferMinutes), $endsAt->copy()->addMinutes($bufferMinutes))
            ->exists();

        abort_if($taken, 409, 'El profesional ya tiene una reserva en ese horario.');
    }
}
