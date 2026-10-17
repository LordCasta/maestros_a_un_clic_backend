<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Quién puede hacer qué sobre una reserva. Las reglas de estado (p. ej. no cancelar
 * una reserva completada) viven en BookingStatus / BookingService, no aquí.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->involves($user) || $user->isAdmin();
    }

    /**
     * Decisión N2: el cliente debe estar verificado para reservar.
     */
    public function create(User $user): Response
    {
        if (! $user->isClient()) {
            return Response::deny('Solo los clientes pueden crear reservas.');
        }

        return $user->isVerified()
            ? Response::allow()
            : Response::deny('Debes verificar tu identidad antes de hacer una reserva.');
    }

    /**
     * HU004: solo el profesional de la reserva acepta o rechaza la solicitud.
     */
    public function respond(User $user, Booking $booking): Response
    {
        return $user->id === $booking->professional_id
            ? Response::allow()
            : Response::deny('Solo el profesional de la reserva puede responder la solicitud.');
    }

    /**
     * HU036: solo el cliente de la reserva la confirma.
     */
    public function confirm(User $user, Booking $booking): Response
    {
        return $user->id === $booking->client_id
            ? Response::allow()
            : Response::deny('Solo el cliente de la reserva puede confirmarla.');
    }

    /**
     * HU027, HU028: solo el profesional de la reserva inicia y finaliza el servicio.
     */
    public function work(User $user, Booking $booking): Response
    {
        return $user->id === $booking->professional_id
            ? Response::allow()
            : Response::deny('Solo el profesional de la reserva puede iniciar o finalizar el servicio.');
    }

    /**
     * HU011: el chat es solo entre el cliente y el profesional de la reserva.
     */
    public function chat(User $user, Booking $booking): bool
    {
        return $booking->involves($user);
    }

    /**
     * HU012: cliente o profesional pueden reprogramar.
     */
    public function reschedule(User $user, Booking $booking): bool
    {
        return $booking->involves($user);
    }

    /**
     * HU012: cliente o profesional pueden cancelar.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->involves($user) || $user->isAdmin();
    }
}
