<?php

use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingAnswered;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

/**
 * Profesional con un servicio y horario de 06:00 a 22:00 todos los días, para que la
 * disponibilidad no interfiera en los tests que no tratan de ella.
 */
function bookableService(array $serviceAttributes = [], string $from = '06:00', string $to = '22:00'): array
{
    [$professional, $service] = professionalWithService($serviceAttributes);

    foreach (range(0, 6) as $weekday) {
        AvailabilityRule::create([
            'professional_profile_id' => $service->professional_profile_id,
            'weekday' => $weekday,
            'start_time' => $from,
            'end_time' => $to,
        ]);
    }

    return [$professional, $service];
}

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
        [$professional, $service] = bookableService(['price' => 80000, 'estimated_duration_minutes' => 120]);
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
        [, $service] = bookableService();
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $utc = Carbon::tomorrow('UTC')->setTime(15, 0); // 10:00 en Colombia
        $payload = ['starts_at' => $utc->format('Y-m-d\TH:i:s\Z')] + bookingPayload($service->id);

        $this->postJson('/api/v1/bookings', $payload)
            ->assertCreated()
            ->assertJsonPath('data.starts_at', $utc->copy()->setTimezone('America/Bogota')->toIso8601String());

        expect(Booking::firstOrFail()->starts_at->format('H:i'))->toBe('10:00');
    });

    it('keeps the agreed price when the professional changes the service price later', function () {
        [, $service] = bookableService(['price' => 80000]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertCreated();
        $service->update(['price' => 120000]);

        expect((float) Booking::firstOrFail()->agreed_price)->toBe(80000.0);
    });

    it('requires the client to be verified', function () {
        [, $service] = bookableService();
        Sanctum::actingAs(User::factory()->client()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))
            ->assertForbidden()
            ->assertJsonPath('message', 'Debes verificar tu identidad antes de hacer una reserva.');
    });

    it('does not let a professional create bookings', function () {
        [, $service] = bookableService();
        Sanctum::actingAs(User::factory()->professional()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertForbidden();
    });

    it('rejects a slot that overlaps another booking including the rest time', function () {
        [, $service] = bookableService(['estimated_duration_minutes' => 60]);
        $service->professionalProfile->update(['buffer_minutes' => 30]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(10, 0)))->assertCreated();

        // Termina 11:00 + 30 min de descanso: 11:15 se cruza, 11:30 no.
        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(11, 15)))->assertConflict();
        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(11, 30)))->assertCreated();
    });

    it('rejects a time outside the professional schedule', function () {
        [, $service] = bookableService(['estimated_duration_minutes' => 120], '08:00', '12:00');
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        // 11:00 + 2 h termina a las 13:00, después del cierre.
        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(11, 0)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('rejects a time blocked by the professional', function () {
        [, $service] = bookableService(['estimated_duration_minutes' => 60]);
        AvailabilityBlock::create([
            'professional_profile_id' => $service->professional_profile_id,
            'starts_at' => Carbon::tomorrow()->setTime(9, 0),
            'ends_at' => Carbon::tomorrow()->setTime(12, 0),
            'reason' => 'Cita médica',
        ]);
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->postJson('/api/v1/bookings', bookingPayload($service->id, Carbon::tomorrow()->setTime(10, 0)))
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('notifies the professional about the new request', function () {
        Notification::fake();
        [$professional, $service] = bookableService();
        Sanctum::actingAs(User::factory()->client()->verified()->create(['name' => 'Camila Restrepo']));

        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertCreated();

        Notification::assertSentTo($professional, BookingRequested::class, function (BookingRequested $notification, array $channels) use ($professional) {
            $data = $notification->toArray($professional);

            return $channels === ['database', 'broadcast']
                && $data['booking_id'] === Booking::firstOrFail()->id
                && str_contains($data['body'], 'Camila Restrepo');
        });
    });

    it('rejects inactive services', function () {
        [, $service] = bookableService(['is_active' => false]);
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

    it('filters by status for the pending requests of the panel', function () {
        $pending = Booking::factory()->create();
        Booking::factory()->status(BookingStatus::Confirmed)->for($pending->professional, 'professional')->create();

        Sanctum::actingAs($pending->professional);
        $this->getJson('/api/v1/bookings?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);

        $this->getJson('/api/v1/bookings?status=unknown')->assertJsonValidationErrors(['status']);
    });

    it('forbids viewing a booking of someone else', function () {
        $booking = Booking::factory()->create();
        Sanctum::actingAs(User::factory()->client()->verified()->create());

        $this->getJson("/api/v1/bookings/{$booking->id}")->assertForbidden();
    });
});

describe('answering a request', function () {
    it('lets the professional accept and notifies the client', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('booking_status_changes', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'accepted',
            'changed_by' => $booking->professional_id,
        ]);
        Notification::assertSentTo($booking->client, BookingAnswered::class,
            fn (BookingAnswered $notification) => $notification->databaseType($booking->client) === 'booking.accepted');
    });

    it('lets the professional reject with an optional reason', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/reject", ['reason' => 'No tengo los repuestos.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('booking_status_changes', [
            'booking_id' => $booking->id,
            'to_status' => 'rejected',
            'reason' => 'No tengo los repuestos.',
        ]);
        Notification::assertSentTo($booking->client, BookingAnswered::class,
            fn (BookingAnswered $notification) => str_contains($notification->toArray($booking->client)['body'], 'No tengo los repuestos.'));

        $other = Booking::factory()->create();
        Sanctum::actingAs($other->professional);
        $this->postJson("/api/v1/bookings/{$other->id}/reject")->assertOk();
    });

    it('frees the time after a rejection', function () {
        [, $service] = bookableService(['estimated_duration_minutes' => 60]);
        $client = User::factory()->client()->verified()->create();
        Sanctum::actingAs($client);
        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertCreated();

        Sanctum::actingAs($service->professionalProfile->user);
        $this->postJson('/api/v1/bookings/'.Booking::firstOrFail()->id.'/reject')->assertOk();

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/bookings', bookingPayload($service->id))->assertCreated();
    });

    it('only lets the professional of the booking answer', function () {
        $booking = Booking::factory()->create();

        Sanctum::actingAs($booking->client);
        $this->postJson("/api/v1/bookings/{$booking->id}/accept")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el profesional de la reserva puede responder la solicitud.');

        Sanctum::actingAs(User::factory()->professional()->verified()->create());
        $this->postJson("/api/v1/bookings/{$booking->id}/reject")->assertForbidden();

        $this->postJson("/api/v1/bookings/{$booking->id}/accept")->assertForbidden();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    });

    it('does not answer a request twice', function () {
        $booking = Booking::factory()->status(BookingStatus::Accepted)->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/reject")->assertConflict();
        $this->postJson("/api/v1/bookings/{$booking->id}/accept")->assertConflict();
    });

    it('requires authentication', function () {
        $booking = Booking::factory()->create();

        $this->postJson("/api/v1/bookings/{$booking->id}/accept")->assertUnauthorized();
    });
});

describe('confirming a booking', function () {
    it('lets the client confirm an accepted booking and notifies the professional', function () {
        Notification::fake();
        $booking = Booking::factory()->status(BookingStatus::Accepted)->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")
            ->assertOk()
            ->assertJsonPath('message', 'Reserva confirmada.')
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('booking_status_changes', [
            'booking_id' => $booking->id,
            'from_status' => 'accepted',
            'to_status' => 'confirmed',
            'changed_by' => $booking->client_id,
        ]);
        Notification::assertSentTo($booking->professional, BookingConfirmed::class,
            fn (BookingConfirmed $notification, array $channels) => $channels === ['database', 'broadcast']
                && $notification->databaseType($booking->professional) === 'booking.confirmed');
    });

    it('does not confirm before the professional accepts', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")->assertConflict();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
        Notification::assertNothingSent();
    });

    it('does not confirm twice', function () {
        $booking = Booking::factory()->status(BookingStatus::Confirmed)->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")->assertConflict();
    });

    it('only lets the client of the booking confirm', function () {
        $booking = Booking::factory()->status(BookingStatus::Accepted)->create();

        Sanctum::actingAs($booking->professional);
        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el cliente de la reserva puede confirmarla.');

        Sanctum::actingAs(User::factory()->client()->verified()->create());
        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'accepted']);
    });

    it('requires authentication to confirm', function () {
        $booking = Booking::factory()->status(BookingStatus::Accepted)->create();

        $this->postJson("/api/v1/bookings/{$booking->id}/confirm")->assertUnauthorized();
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
