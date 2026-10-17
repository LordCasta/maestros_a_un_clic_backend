<?php

use App\Enums\BookingStatus;
use App\Events\MessageSent;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('sending messages', function () {
    it('sends a text message, broadcasts it and notifies the other party', function () {
        Event::fake([MessageSent::class]);
        Notification::fake();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->client);

        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => '¿Debo comprar algún repuesto?'])
            ->assertCreated()
            ->assertJsonPath('data.body', '¿Debo comprar algún repuesto?')
            ->assertJsonPath('data.sender.id', $booking->client_id)
            ->assertJsonPath('data.attachment_url', null);

        Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->broadcastOn()->name === "presence-booking.{$booking->id}"
            && $event->broadcastAs() === 'message.sent');
        Notification::assertSentTo($booking->professional, MessageReceived::class,
            fn (MessageReceived $n) => $n->toArray($booking->professional)['body'] === '¿Debo comprar algún repuesto?');
        Notification::assertNotSentTo($booking->client, MessageReceived::class);
    });

    it('sends a photo of the problem to the private disk', function () {
        Storage::fake('local');
        Notification::fake();
        $booking = Booking::factory()->status(BookingStatus::Confirmed)->create();
        Sanctum::actingAs($booking->client);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/messages", [
            'attachment' => UploadedFile::fake()->image('fuga.jpg', 800, 600),
        ])->assertCreated();

        $message = $booking->messages()->firstOrFail();
        Storage::disk('local')->assertExists($message->attachment_path);
        expect($message->attachment_path)->toStartWith("chat/{$booking->id}/")
            ->and($response->json('data.attachment_url'))->toContain('expiration='); // URL temporal (en el disco real, además firmada)

        Notification::assertSentTo($booking->professional, MessageReceived::class,
            fn (MessageReceived $n) => $n->toArray($booking->professional)['body'] === 'Te envió una imagen.');
    });

    it('validates the message and the image', function () {
        Storage::fake('local');
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/messages", [])
            ->assertJsonValidationErrors(['body' => 'Escribe un mensaje o adjunta una imagen.']);
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['attachment' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertJsonValidationErrors(['attachment']);
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['attachment' => UploadedFile::fake()->image('a.jpg')->size(6000)])
            ->assertJsonValidationErrors(['attachment']);
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => str_repeat('a', 2001)])
            ->assertJsonValidationErrors(['body']);
    });

    it('only allows writing while the booking is active', function () {
        foreach ([BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::Rejected] as $status) {
            $booking = Booking::factory()->status($status)->create();
            Sanctum::actingAs($booking->client);

            $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => 'Hola'])->assertConflict();
            $this->getJson("/api/v1/bookings/{$booking->id}/messages")->assertOk()->assertJsonPath('data.can_send', false);
        }
    });

    it('is only for the client and the professional of the booking', function () {
        $booking = Booking::factory()->create();

        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => 'Hola'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->client()->verified()->create());
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => 'Hola'])->assertForbidden();
        $this->getJson("/api/v1/bookings/{$booking->id}/messages")->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson("/api/v1/bookings/{$booking->id}/messages")->assertForbidden();
    });
});

describe('reading the conversation', function () {
    it('returns the history oldest first and marks the received messages as read', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->client);
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => 'Hola'])->assertCreated();
        Sanctum::actingAs($booking->professional);
        $this->postJson("/api/v1/bookings/{$booking->id}/messages", ['body' => 'Llevo los materiales; tarda unas 2 horas.'])->assertCreated();

        Sanctum::actingAs($booking->client);
        $this->getJson("/api/v1/bookings/{$booking->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.can_send', true)
            ->assertJsonCount(2, 'data.messages')
            ->assertJsonPath('data.messages.0.body', 'Hola')
            ->assertJsonPath('data.messages.1.read_at', null);

        $this->postJson("/api/v1/bookings/{$booking->id}/messages/read")
            ->assertOk()
            ->assertJsonPath('data.marked', 1);

        expect($booking->messages()->where('sender_id', $booking->professional_id)->value('read_at'))->not->toBeNull()
            ->and($booking->messages()->where('sender_id', $booking->client_id)->value('read_at'))->toBeNull();
    });
});
