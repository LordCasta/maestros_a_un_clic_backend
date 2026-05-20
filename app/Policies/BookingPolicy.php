<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id || $user->id === $booking->professional_id || $user->role === 'admin';
    }

    public function cancel(User $user, Booking $booking): bool
    {
        // clients can cancel their bookings, professionals and admins cannot cancel on behalf
        return $user->id === $booking->client_id || $user->role === 'admin';
    }
}

