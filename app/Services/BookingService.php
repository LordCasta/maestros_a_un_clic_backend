<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\AvailabilityChanged;
use App\Events\BookingStatusChanged;
use App\Models\Booking;
use App\Models\ProfessionalProfile;
use App\Models\ProfessionalService;
use App\Models\User;
use App\Notifications\BookingAnswered;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingProgressed;
use App\Notifications\BookingRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de las reservas. Todo cambio de estado pasa por transition()
 * para respetar la máquina de estados (BookingStatus) y dejar auditoría.
 */
class BookingService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @param  array<string, mixed>  $data  Datos validados por StoreBookingRequest.
     */
    public function create(User $client, array $data): Booking
    {
        $service = ProfessionalService::with('professionalProfile.user')->findOrFail($data['professional_service_id']);
        $profile = $service->professionalProfile;
        $professional = $profile->user;

        if (! $service->is_active || ! User::publiclyListed()->whereKey($professional->id)->exists()) {
            throw ValidationException::withMessages([
                'professional_service_id' => 'Este servicio no está disponible para reservas.',
            ]);
        }

        // Normaliza a la zona de la app: Eloquent guarda la hora "de reloj" sin convertir.
        $startsAt = Carbon::parse($data['starts_at'])->setTimezone(config('app.timezone'));
        $endsAt = $startsAt->copy()->addMinutes($service->estimated_duration_minutes);

        if (! $this->availability->withinSchedule($profile, $startsAt, $endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => 'El profesional no atiende en ese horario. Elige una de las horas disponibles.',
            ]);
        }

        $booking = DB::transaction(function () use ($client, $professional, $profile, $service, $data, $startsAt, $endsAt) {
            // Bloquea la agenda del profesional: dos solicitudes simultáneas no pueden tomar la misma hora.
            ProfessionalProfile::whereKey($profile->id)->lockForUpdate()->first();

            abort_if(
                $this->availability->hasBookingConflict($profile, $startsAt, $endsAt),
                409,
                'El profesional ya tiene una reserva en ese horario.',
            );

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

        $professional->notify(new BookingRequested($booking));
        AvailabilityChanged::dispatch($professional->id);

        return $booking;
    }

    /**
     * HU004: el profesional acepta. Queda en `accepted` hasta que el cliente confirme (HU036).
     */
    public function accept(Booking $booking, User $by): Booking
    {
        $booking = $this->transition($booking, BookingStatus::Accepted, $by);
        $booking->client->notify(new BookingAnswered($booking));

        return $booking;
    }

    /**
     * HU004: el profesional rechaza; la hora vuelve a quedar libre.
     */
    public function reject(Booking $booking, User $by, ?string $reason): Booking
    {
        $booking = $this->transition($booking, BookingStatus::Rejected, $by, $reason);
        $booking->client->notify(new BookingAnswered($booking, $reason));

        return $booking;
    }

    /**
     * HU036: el cliente confirma una reserva aceptada; queda en la agenda del profesional.
     */
    public function confirm(Booking $booking, User $by): Booking
    {
        $booking = $this->transition($booking, BookingStatus::Confirmed, $by);
        $booking->professional->notify(new BookingConfirmed($booking));

        return $booking;
    }

    /**
     * HU027: el profesional inicia el servicio, desde la hora pactada.
     */
    public function start(Booking $booking, User $by): Booking
    {
        abort_if(
            Carbon::now()->lt($booking->starts_at),
            409,
            'Solo puedes iniciar el servicio desde la hora programada ('
                .$booking->starts_at->copy()->locale('es')->isoFormat('D [de] MMMM, h:mm a').').',
        );

        $booking = $this->transition($booking, BookingStatus::InProgress, $by, attributes: ['started_at' => Carbon::now()]);
        $booking->client->notify(new BookingProgressed($booking));

        return $booking;
    }

    /**
     * HU028: el profesional finaliza el servicio; desde ahí se puede calificar (HU005).
     */
    public function complete(Booking $booking, User $by): Booking
    {
        $booking = $this->transition($booking, BookingStatus::Completed, $by, attributes: ['completed_at' => Carbon::now()]);
        $booking->client->notify(new BookingProgressed($booking));

        return $booking;
    }

    public function cancel(Booking $booking, User $by, string $reason): Booking
    {
        return $this->transition($booking, BookingStatus::Cancelled, $by, $reason);
    }

    /**
     * Cambia el estado validando la transición y registra el cambio en booking_status_changes.
     *
     * @param  array<string, mixed>  $attributes  Otros campos que cambian junto con el estado (p. ej. started_at).
     */
    public function transition(Booking $booking, BookingStatus $to, User $by, ?string $reason = null, array $attributes = []): Booking
    {
        abort_unless(
            $booking->status->canTransitionTo($to),
            409,
            "No se puede pasar una reserva de '{$booking->status->value}' a '{$to->value}'.",
        );

        $from = $booking->status;

        $booking = DB::transaction(function () use ($booking, $to, $by, $reason, $attributes) {
            $booking->statusChanges()->create([
                'from_status' => $booking->status,
                'to_status' => $to,
                'changed_by' => $by->id,
                'reason' => $reason,
            ]);

            $booking->update(['status' => $to] + $attributes);

            return $booking;
        });

        // Quien tenga abierto el detalle ve el estado nuevo al instante (HU026).
        BookingStatusChanged::dispatch($booking);

        // Rechazar o cancelar libera la hora: las horas libres de los clientes se actualizan (HU025).
        if ($from->occupiesSchedule() !== $to->occupiesSchedule()) {
            AvailabilityChanged::dispatch($booking->professional_id);
        }

        return $booking;
    }
}
