<?php

use App\Broadcasting\BookingChannel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de tiempo real (Laravel Reverb)
|--------------------------------------------------------------------------
| El frontend se autentica contra POST /api/v1/broadcasting/auth con su token Bearer.
| Ver docs/tiempo-real.md.
*/

// Notificaciones del usuario (HU018). Nombre por convención de Laravel.
Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id) => $user->id === $id);

// Agenda pública de un profesional: `professional.{id}.availability` (HU003, HU025).
// Es un canal público (sin autorización): solo anuncia que cambió, sin datos personales.

// Chat y presencia de una reserva (HU011, HU041).
Broadcast::channel('booking.{booking}', BookingChannel::class);
