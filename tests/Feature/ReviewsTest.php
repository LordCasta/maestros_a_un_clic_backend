<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

function createCompletedBooking(?User $client = null, ?User $professional = null): Booking
{
    [$pro, $service] = professionalWithService();
    $prof = $professional ?? $pro;
    $cli = $client ?? User::factory()->client()->verified()->create();

    return Booking::create([
        'client_id' => $cli->id,
        'professional_id' => $prof->id,
        'professional_service_id' => $service->id,
        'description' => 'Servicio terminado.',
        'address' => 'Calle 10 # 43-20',
        'starts_at' => Carbon::yesterday()->setTime(10, 0),
        'ends_at' => Carbon::yesterday()->setTime(12, 0),
        'status' => BookingStatus::Completed,
        'agreed_price' => 80000,
        'started_at' => Carbon::yesterday()->setTime(10, 0),
        'completed_at' => Carbon::yesterday()->setTime(12, 0),
    ]);
}

describe('HU005: rating and reviews', function () {
    it('allows a client to review a completed booking and updates professional rating', function () {
        Notification::fake();

        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($client);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
            'comment' => 'Excelente servicio, muy puntual y profesional.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.booking_id', $booking->id)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Excelente servicio, muy puntual y profesional.')
            ->assertJsonPath('data.reviewer.id', $client->id)
            ->assertJsonPath('data.reviewee.id', $professional->id);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'reviewer_id' => $client->id,
            'reviewee_id' => $professional->id,
            'rating' => 5,
            'comment' => 'Excelente servicio, muy puntual y profesional.',
        ]);

        $professional->refresh();
        expect($professional->rating_avg)->toEqual(5.0)
            ->and($professional->rating_count)->toBe(1);

        Notification::assertSentTo($professional, ReviewReceived::class);
    });

    it('allows a professional to review a completed booking reciprocally and updates client rating', function () {
        Notification::fake();

        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($professional);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 4,
            'comment' => 'Muy amable y colaboradora.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.booking_id', $booking->id)
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Muy amable y colaboradora.')
            ->assertJsonPath('data.reviewer.id', $professional->id)
            ->assertJsonPath('data.reviewee.id', $client->id);

        $client->refresh();
        expect($client->rating_avg)->toEqual(4.0)
            ->and($client->rating_count)->toBe(1);

        Notification::assertSentTo($client, ReviewReceived::class);
    });

    it('allows both client and professional to rate the same completed booking', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        // Client reviews professional
        Sanctum::actingAs($client);
        $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
        ])->assertCreated();

        // Professional reviews client
        Sanctum::actingAs($professional);
        $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 4,
        ])->assertCreated();

        expect(Review::where('booking_id', $booking->id)->count())->toBe(2);
    });

    it('calculates average rating accurately when multiple reviews exist', function () {
        [$professional, $service] = professionalWithService();

        $booking1 = createCompletedBooking(null, $professional);
        $booking2 = createCompletedBooking(null, $professional);

        Sanctum::actingAs($booking1->client);
        $this->postJson("/api/v1/bookings/{$booking1->id}/review", ['rating' => 5])->assertCreated();

        Sanctum::actingAs($booking2->client);
        $this->postJson("/api/v1/bookings/{$booking2->id}/review", ['rating' => 4])->assertCreated();

        $professional->refresh();
        expect($professional->rating_avg)->toEqual(4.5)
            ->and($professional->rating_count)->toBe(2);
    });

    it('rejects review if booking is not completed', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();

        $booking = createCompletedBooking($client, $professional);
        $booking->update(['status' => BookingStatus::InProgress]);

        Sanctum::actingAs($client);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Solo se pueden calificar reservas completadas.');
    });

    it('prevents reviewing the same booking twice by the same user', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
        ])->assertCreated();

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 4,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Ya calificaste esta reserva.');
    });

    it('forbids a non-participant from reviewing the booking', function () {
        $stranger = User::factory()->client()->verified()->create();
        $booking = createCompletedBooking();

        Sanctum::actingAs($stranger);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
        ]);

        $response->assertForbidden();
    });

    it('requires authentication to review', function () {
        $booking = createCompletedBooking();

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
        ]);

        $response->assertUnauthorized();
    });

    it('validates rating is required, integer, and between 1 and 5', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 'cinco'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    });

    it('allows optional comment and rejects comments longer than 1000 characters', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
            'comment' => null,
        ])->assertCreated();

        $booking2 = createCompletedBooking($client, $professional);
        $this->postJson("/api/v1/bookings/{$booking2->id}/review", [
            'rating' => 5,
            'comment' => str_repeat('a', 1001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['comment']);
    });

    it('returns reviews in booking detail show endpoint', function () {
        $client = User::factory()->client()->verified()->create();
        [$professional, $service] = professionalWithService();
        $booking = createCompletedBooking($client, $professional);

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", [
            'rating' => 5,
            'comment' => 'Servicio impecable.',
        ])->assertCreated();

        $detailResponse = $this->getJson("/api/v1/bookings/{$booking->id}");
        $detailResponse->assertOk()
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath('data.reviews.0.comment', 'Servicio impecable.')
            ->assertJsonPath('data.reviews.0.reviewer.id', $client->id);
    });
});
