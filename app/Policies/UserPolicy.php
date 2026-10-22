<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Quién puede ver los datos de otro usuario.
 */
class UserPolicy
{
    /**
     * HU044: el profesional ve el perfil de un cliente que le ha pedido un servicio
     * (se accede desde la solicitud). No es un directorio abierto de clientes.
     */
    public function viewClientProfile(User $viewer, User $client): bool
    {
        return $viewer->isProfessional()
            && $client->isClient()
            && Booking::where('professional_id', $viewer->id)->where('client_id', $client->id)->exists();
    }
}
