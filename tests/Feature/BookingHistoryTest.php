<?php

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

it('shows the status history of a booking to its participants, oldest first', function () {
    $booking = Booking::factory()->create();
    $booking->statusChanges()->create(['from_status' => null, 'to_status' => BookingStatus::Pending, 'changed_by' => $booking->client_id]);
    Sanctum::actingAs($booking->professional);
    $this->postJson("/api/v1/bookings/{$booking->id}/reject", ['reason' => 'No tengo los repuestos.'])->assertOk();

    foreach ([$booking->client, $booking->professional] as $participant) {
        Sanctum::actingAs($participant);

        $this->getJson("/api/v1/bookings/{$booking->id}/history")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.from_status', null)
            ->assertJsonPath('data.0.to_status', 'pending')
            ->assertJsonPath('data.1.from_status', 'pending')
            ->assertJsonPath('data.1.to_status', 'rejected')
            ->assertJsonPath('data.1.reason', 'No tengo los repuestos.')
            ->assertJsonPath('data.1.changed_by.name', $booking->professional->name)
            ->assertJsonPath('data.1.previous_starts_at', null);
    }
});

it('hides the history from other users', function () {
    $booking = Booking::factory()->create();

    Sanctum::actingAs(User::factory()->client()->verified()->create());
    $this->getJson("/api/v1/bookings/{$booking->id}/history")->assertForbidden();

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson("/api/v1/bookings/{$booking->id}/history")->assertOk();
});

it('announces every status change on the booking channel', function () {
    Event::fake([BookingStatusChanged::class]);
    $booking = Booking::factory()->create();
    Sanctum::actingAs($booking->professional);

    $this->postJson("/api/v1/bookings/{$booking->id}/accept")->assertOk();

    Event::assertDispatched(BookingStatusChanged::class, function (BookingStatusChanged $event) use ($booking) {
        return $event->broadcastOn()->name === "presence-booking.{$booking->id}"
            && $event->broadcastAs() === 'booking.status-changed'
            && $event->broadcastWith()['status'] === BookingStatus::Accepted;
    });
});
