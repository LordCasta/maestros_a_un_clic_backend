<?php

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingRequested;
use Laravel\Sanctum\Sanctum;

/** Crea $count notificaciones reales de solicitud para el profesional de la reserva. */
function requestNotifications(Booking $booking, int $count = 1): void
{
    foreach (range(1, $count) as $_) {
        $booking->professional->notify(new BookingRequested($booking));
    }
}

it('lists the notifications of the user, newest first, with the same shape as the websocket', function () {
    $booking = Booking::factory()->create();
    requestNotifications($booking, 2);
    requestNotifications(Booking::factory()->create());
    Sanctum::actingAs($booking->professional);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.type', 'booking.requested')
        ->assertJsonPath('data.0.booking_id', $booking->id)
        ->assertJsonPath('data.0.title', 'Nueva solicitud de reserva')
        ->assertJsonPath('data.0.read_at', null);
});

it('counts the unread ones and marks one or all as read', function () {
    $booking = Booking::factory()->create();
    requestNotifications($booking, 3);
    Sanctum::actingAs($booking->professional);

    $this->getJson('/api/v1/notifications?unread=1&per_page=1')->assertJsonPath('meta.total', 3);

    $id = $this->getJson('/api/v1/notifications')->json('data.0.id');
    $this->postJson("/api/v1/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('data.read_at', fn (?string $at) => $at !== null);
    $this->getJson('/api/v1/notifications?unread=1')->assertJsonPath('meta.total', 2);

    $this->postJson('/api/v1/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('data.marked', 2);
    $this->getJson('/api/v1/notifications?unread=1')->assertJsonPath('meta.total', 0);
});

it('does not let a user read the notifications of someone else', function () {
    $booking = Booking::factory()->create();
    requestNotifications($booking);
    $id = $booking->professional->notifications()->value('id');

    Sanctum::actingAs(User::factory()->client()->verified()->create());
    $this->postJson("/api/v1/notifications/{$id}/read")->assertNotFound();
    $this->getJson('/api/v1/notifications')->assertJsonCount(0, 'data');

    expect($booking->professional->unreadNotifications()->count())->toBe(1);
});

it('requires authentication', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});
