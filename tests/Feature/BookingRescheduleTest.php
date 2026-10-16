<?php

use App\Enums\BookingStatus;
use App\Events\AvailabilityChanged;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

/**
 * Reserva de 60 min mañana a las 10:00 con un profesional que atiende de 08:00 a 18:00 todos los días.
 */
function reschedulableBooking(BookingStatus $status = BookingStatus::Confirmed): Booking
{
    [, $service] = professionalWithService(['estimated_duration_minutes' => 60]);

    foreach (range(0, 6) as $weekday) {
        AvailabilityRule::create([
            'professional_profile_id' => $service->professional_profile_id,
            'weekday' => $weekday,
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);
    }

    return Booking::factory()->status($status)->for($service, 'service')->create([
        'starts_at' => Carbon::tomorrow()->setTime(10, 0),
        'ends_at' => Carbon::tomorrow()->setTime(11, 0),
    ]);
}

describe('rescheduling', function () {
    it('moves the booking, sends it back to pending and keeps the previous date', function () {
        Notification::fake();
        Event::fake([AvailabilityChanged::class]);
        $booking = reschedulableBooking();
        Sanctum::actingAs($booking->client);
        $newStart = Carbon::tomorrow()->setTime(15, 0);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => $newStart->toIso8601String()])
            ->assertOk()
            ->assertJsonPath('message', 'Reserva reprogramada. El profesional debe aceptar el nuevo horario.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.starts_at', $newStart->toIso8601String())
            ->assertJsonPath('data.ends_at', $newStart->copy()->addHour()->toIso8601String());

        $change = $booking->statusChanges()->latest('id')->firstOrFail();
        expect($change->from_status)->toBe(BookingStatus::Confirmed)
            ->and($change->to_status)->toBe(BookingStatus::Pending)
            ->and($change->previous_starts_at->equalTo(Carbon::tomorrow()->setTime(10, 0)))->toBeTrue();

        Notification::assertSentTo($booking->professional, BookingRescheduled::class);
        Notification::assertNotSentTo($booking->client, BookingRescheduled::class);
        Event::assertDispatched(AvailabilityChanged::class);
    });

    it('lets the professional reschedule a pending request too', function () {
        Notification::fake();
        $booking = reschedulableBooking(BookingStatus::Pending);
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(9, 0)->toIso8601String()])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        Notification::assertSentTo($booking->client, BookingRescheduled::class);
    });

    it('can overlap its own previous time, but not another booking', function () {
        $booking = reschedulableBooking();
        Booking::factory()->for($booking->service, 'service')->create([
            'starts_at' => Carbon::tomorrow()->setTime(14, 0),
            'ends_at' => Carbon::tomorrow()->setTime(15, 0),
        ]);
        Sanctum::actingAs($booking->client);

        // 10:30 se cruza solo con la propia reserva (10:00–11:00): se permite.
        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(10, 30)->toIso8601String()])
            ->assertOk();

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(14, 30)->toIso8601String()])
            ->assertConflict();
    });

    it('only accepts a time inside the professional schedule', function () {
        $booking = reschedulableBooking();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(17, 30)->toIso8601String()])
            ->assertJsonValidationErrors(['starts_at']);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::yesterday()->toIso8601String()])
            ->assertJsonValidationErrors(['starts_at']);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule")->assertJsonValidationErrors(['starts_at']);
    });

    it('does not reschedule after the scheduled time or once the service started', function () {
        $booking = reschedulableBooking(BookingStatus::InProgress);
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String()])
            ->assertConflict();

        $late = reschedulableBooking();
        Carbon::setTestNow(Carbon::tomorrow()->setTime(10, 0));
        Sanctum::actingAs($late->client);

        $this->postJson("/api/v1/bookings/{$late->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String()])
            ->assertConflict();

        Carbon::setTestNow();
    });

    it('is only for the participants', function () {
        $booking = reschedulableBooking();

        Sanctum::actingAs(User::factory()->client()->verified()->create());
        $this->postJson("/api/v1/bookings/{$booking->id}/reschedule", ['starts_at' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String()])
            ->assertForbidden();
    });
});

describe('cancelling', function () {
    it('notifies the other party with the reason', function () {
        Notification::fake();
        $booking = reschedulableBooking();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Me salió un viaje de trabajo.'])->assertOk();

        Notification::assertSentTo($booking->professional, BookingCancelled::class,
            fn (BookingCancelled $notification) => str_contains($notification->toArray($booking->professional)['body'], 'Me salió un viaje de trabajo.'));
        Notification::assertNotSentTo($booking->client, BookingCancelled::class);
    });

    it('notifies both parties when the admin cancels', function () {
        Notification::fake();
        $booking = reschedulableBooking();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Reporte de suplantación.'])->assertOk();

        Notification::assertSentTo([$booking->client, $booking->professional], BookingCancelled::class);
    });

    it('only cancels before the scheduled time', function () {
        $booking = reschedulableBooking();
        Carbon::setTestNow(Carbon::tomorrow()->setTime(10, 0));
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Ya no lo necesito.'])
            ->assertConflict()
            ->assertJsonPath('message', 'La hora de la reserva ya llegó; ya no se puede cancelar.');

        Carbon::setTestNow();
    });
});
