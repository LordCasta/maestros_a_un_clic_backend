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
     * HU012: cliente o profesional pueden cancelar.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->involves($user) || $user->isAdmin();
    }
}
