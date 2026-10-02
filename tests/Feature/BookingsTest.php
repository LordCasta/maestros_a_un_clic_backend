<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

function bookingPayload(int $serviceId, ?Carbon $startsAt = null): array
{
    return [
        'professional_service_id' => $serviceId,
        'starts_at' => ($startsAt ?? Carbon::tomorrow()->setTime(10, 0))->toIso8601String(),
        'description' => 'Se está saliendo el agua debajo del lavaplatos.',
        'address' => 'Calle 10 # 43-20',
    ];
}

describe('creating a booking', function () {
    it('lets a verified client book a service', function () {
        [$professional, $service] = professionalWithService(['price' => 80000, 'estimated_duration_minutes' => 120]);
        $client = User::factory()->client()->verified()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(10, 0)));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.agreed_price', 80000)
            ->assertJsonPath('data.professional.id', $professional->id)
            ->assertJsonPath('data.client.id', $client->id);

        $booking = Booking::firstOrFail();

        expect($booking->ends_at->equalTo($booking->starts_at->copy()->addMinutes(120)))->toBeTrue()
            ->and($booking->statusChanges()->count())->toBe(1);
    });

    it('stores the time in Colombia even if the client sends it in UTC', function () {
        [, $service] = professionalWithService();
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $utc = Carbon::tomorrow('UTC')->setTime(15, 0); // 10:00 en Colombia
        $payload = ['starts_at' => $utc->format('Y-m-d\TH:i:s\Z')] + bookingPayload($service->id);

        $this->postJson('/api/v1/bookings', $payload)
            ->assertCreated()
            ->assertJsonPath('data.starts_at', $utc->copy()->setTimezone('America/Bogota')->toIso8601String());

        expect(Booking::firstOrFail()->starts_at->format('H:i'))->toBe('10:00');
    });

    it('keeps the agreed price when the professional changes the service price later', function () {
        [, $service] = professionalWithService(['price' => 80000]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertCreated();
        $service->update(['price' => 120000]);

        expect((float) Booking::firstOrFail()->agreed_price)->toBe(80000.0);
    });

    it('requires the client to be verified', function () {
        [, $service] = professionalWithService();
        Sanctum::actingAs(User::factory()->client()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))
            ->assertForbidden()
            ->assertJsonPath('message', 'Debes verificar tu identidad antes de hacer una reserva.');
    });

    it('does not let a professional create bookings', function () {
        [, $service] = professionalWithService();
        Sanctum::actingAs(User::factory()->professional()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertForbidden();
    });

    it('rejects a slot that overlaps another booking including the rest time', function () {
        [, $service] = professionalWithService(['estimated_duration_minutes' => 60]);
        $service->professionalProfile->update(['buffer_minutes' => 30]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(10, 0)))->assertCreated();

        // Termina 11:00 + 30 min de descanso: 11:15 se cruza, 11:30 no.
        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(11, 15)))->assertConflict();
        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(11, 30)))->assertCreated();
    });

    it('rejects inactive services', function () {
        [, $service] = professionalWithService(['is_active' => false]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))
            ->assertJsonValidationErrors(['professional_service_id']);
    });
});

describe('listing and viewing bookings', function () {
    it('lists only the bookings of the authenticated user by role', function () {
        $mine = Booking::factory()->create();
        Booking::factory()->create();

        Sanctum::actingAs($mine->client);
        $this->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('meta.total', 1);

        Sanctum::actingAs($mine->professional);
        $this->getJson('/api/v1/bookings')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    });

    it('forbids viewing a booking of someone else', function () {
        $booking = Booking::factory()->create();
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->getJson("/api/v1/bookings/{$booking->id}")->assertForbidden();
    });
});

describe('cancelling a booking', function () {
    it('cancels with a reason and records the change', function () {
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Tengo una emergencia familiar.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('booking_status_changes', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'reason' => 'Tengo una emergencia familiar.',
        ]);
    });

    it('requires a reason', function () {
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel")->assertJsonValidationErrors(['reason']);
    });

    it('does not cancel a completed booking', function () {
        $booking = Booking::factory()->status(BookingStatus::Completed)->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Ya no lo necesito.'])
            ->assertConflict()
            ->assertJsonPath('success', false);
    });
});
