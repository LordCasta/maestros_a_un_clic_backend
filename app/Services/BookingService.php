<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingService
{
    public function create(array $data, $clientId): Booking
    {
        // Basic creation; conflict checks could be added here
        $booking = Booking::create([
            'client_id' => $clientId,
            'professional_id' => $data['professional_id'],
            'service_description' => $data['service_description'] ?? null,
            'scheduled_date' => $data['scheduled_date'],
            'status' => 'pending',
            'total' => $data['total'] ?? null,
        ]);

        return $booking;
    }
}

