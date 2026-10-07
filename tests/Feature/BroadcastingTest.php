<?php

use App\Broadcasting\BookingChannel;
use App\Models\Booking;
use App\Models\User;

it('lets the client and the professional join the booking channel', function () {
    $booking = Booking::factory()->create();
    $channel = new BookingChannel;

    expect($channel->join($booking->client, $booking))
        ->toMatchArray(['id' => $booking->client_id, 'name' => $booking->client->name])
        ->and($channel->join($booking->professional, $booking))
        ->toMatchArray(['id' => $booking->professional_id]);
});

it('does not let other users join the booking channel', function () {
    $booking = Booking::factory()->create();

    expect((new BookingChannel)->join(User::factory()->create(), $booking))->toBeFalse();
});

it('requires a token to authorize channels', function () {
    $this->postJson('/api/v1/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'presence-booking.1',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});
