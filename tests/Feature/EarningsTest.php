<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

afterEach(fn () => Carbon::setTestNow());

/** Reserva finalizada del servicio con el precio y la fecha de finalización dados. */
function completedBooking(ProfessionalService $service, float $price, string $completedAt, BookingStatus $status = BookingStatus::Completed): Booking
{
    return Booking::factory()->status($status)->for($service, 'service')->create([
        'agreed_price' => $price,
        'completed_at' => Carbon::parse($completedAt, 'America/Bogota'),
    ]);
}

it('sums the completed services of the current month by default', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-20 12:00', 'America/Bogota'));
    [$professional, $service] = professionalWithService();
    completedBooking($service, 80000, '2026-10-02 11:00');
    completedBooking($service, 120000, '2026-10-02 16:00');
    completedBooking($service, 50000, '2026-10-15 10:00');
    completedBooking($service, 999999, '2026-09-30 18:00');                       // mes anterior
    completedBooking($service, 999999, '2026-10-05 10:00', BookingStatus::Cancelled); // no finalizada
    Booking::factory()->status(BookingStatus::Completed)->create(['completed_at' => now()]); // de otro
    Sanctum::actingAs($professional);

    $this->getJson('/api/v1/professional/earnings')
        ->assertOk()
        ->assertJsonPath('data.from', '2026-10-01')
        ->assertJsonPath('data.to', '2026-10-20')
        ->assertJsonPath('data.total', 250000)
        ->assertJsonPath('data.count', 3)
        ->assertJsonPath('data.average', 83333.33)
        ->assertJsonCount(2, 'data.by_day')
        ->assertJsonPath('data.by_day.0', ['date' => '2026-10-02', 'total' => 200000, 'count' => 2])
        ->assertJsonCount(3, 'data.bookings')
        ->assertJsonPath('data.bookings.0.agreed_price', 50000);
});

it('filters by a date range', function () {
    [$professional, $service] = professionalWithService();
    completedBooking($service, 80000, '2026-09-10 10:00');
    completedBooking($service, 60000, '2026-09-30 23:30'); // último minuto del rango, en hora de Colombia
    completedBooking($service, 70000, '2026-10-01 00:10');
    Sanctum::actingAs($professional);

    $this->getJson('/api/v1/professional/earnings?from=2026-09-01&to=2026-09-30')
        ->assertOk()
        ->assertJsonPath('data.total', 140000)
        ->assertJsonPath('data.count', 2);
});

it('validates the range', function () {
    [$professional] = professionalWithService();
    Sanctum::actingAs($professional);

    $this->getJson('/api/v1/professional/earnings?from=2026-10-10&to=2026-10-01')->assertJsonValidationErrors(['to']);
    $this->getJson('/api/v1/professional/earnings?from=2026-10-10')->assertJsonValidationErrors(['to']);
    $this->getJson('/api/v1/professional/earnings?from=2024-01-01&to=2026-01-01')
        ->assertJsonValidationErrors(['to' => 'Consulta como máximo un año a la vez.']);
    $this->getJson('/api/v1/professional/earnings?from=10-10-2026&to=2026-10-11')->assertJsonValidationErrors(['from']);
});

it('is only for professionals', function () {
    $this->getJson('/api/v1/professional/earnings')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->client()->verified()->create());
    $this->getJson('/api/v1/professional/earnings')->assertForbidden();
});
