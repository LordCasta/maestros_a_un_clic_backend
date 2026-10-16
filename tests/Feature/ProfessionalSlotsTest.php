<?php

use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Servicio de 60 min de un profesional que atiende de 08:00 a 12:00 el día de $date.
 */
function serviceWithMorningSchedule(Carbon $date, int $bufferMinutes = 0): ProfessionalService
{
    [, $service] = professionalWithService(['estimated_duration_minutes' => 60]);
    $service->professionalProfile->update(['buffer_minutes' => $bufferMinutes]);

    AvailabilityRule::create([
        'professional_profile_id' => $service->professional_profile_id,
        'weekday' => $date->dayOfWeek,
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);

    return $service;
}

function slotsUrl(ProfessionalService $service, Carbon $date): string
{
    $professionalId = $service->professionalProfile->user_id;

    return "/api/v1/professionals/{$professionalId}/slots?date={$date->toDateString()}&service_id={$service->id}";
}

/** @return list<string> horas de inicio "HH:MM" de la respuesta */
function slotTimes($response): array
{
    return array_map(fn (array $slot) => Carbon::parse($slot['starts_at'])->format('H:i'), $response->json('data.slots'));
}

it('lists the start times that fit in the schedule every 30 minutes', function () {
    $date = Carbon::tomorrow();
    $service = serviceWithMorningSchedule($date);

    $response = $this->getJson(slotsUrl($service, $date))
        ->assertOk()
        ->assertJsonPath('data.date', $date->toDateString())
        ->assertJsonPath('data.duration_minutes', 60);

    expect(slotTimes($response))->toBe(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00'])
        ->and($response->json('data.slots.0.ends_at'))->toBe($date->copy()->setTime(9, 0)->toIso8601String());
});

it('leaves out times taken by bookings, including the rest time, and blocks', function () {
    $date = Carbon::tomorrow();
    $service = serviceWithMorningSchedule($date, bufferMinutes: 30);

    Booking::factory()->for($service, 'service')->create([
        'professional_id' => $service->professionalProfile->user_id,
        'starts_at' => $date->copy()->setTime(9, 0),
        'ends_at' => $date->copy()->setTime(10, 0),
    ]);
    Booking::factory()->status(BookingStatus::Cancelled)->for($service, 'service')->create([
        'professional_id' => $service->professionalProfile->user_id,
        'starts_at' => $date->copy()->setTime(11, 0),
        'ends_at' => $date->copy()->setTime(12, 0),
    ]);
    AvailabilityBlock::create([
        'professional_profile_id' => $service->professional_profile_id,
        'starts_at' => $date->copy()->setTime(8, 0),
        'ends_at' => $date->copy()->setTime(8, 30),
    ]);

    // Ocupado: 08:00–08:30 (bloqueo) y 08:30–10:30 (reserva 9–10 con 30 min de descanso a cada lado).
    // La reserva cancelada no ocupa agenda.
    expect(slotTimes($this->getJson(slotsUrl($service, $date))))->toBe(['10:30', '11:00']);
});

it('returns no slots on a day without schedule', function () {
    $date = Carbon::tomorrow();
    $service = serviceWithMorningSchedule($date);

    $this->getJson(slotsUrl($service, $date->copy()->addDay()))
        ->assertOk()
        ->assertJsonCount(0, 'data.slots');
});

it('leaves out times that already passed today', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 15));
    $service = serviceWithMorningSchedule(Carbon::today());

    expect(slotTimes($this->getJson(slotsUrl($service, Carbon::today()))))->toBe(['09:30', '10:00', '10:30', '11:00']);

    Carbon::setTestNow();
});

it('validates the date and the service', function () {
    $date = Carbon::tomorrow();
    $service = serviceWithMorningSchedule($date);
    $professionalId = $service->professionalProfile->user_id;

    $this->getJson("/api/v1/professionals/{$professionalId}/slots")
        ->assertJsonValidationErrors(['date', 'service_id']);

    $this->getJson(slotsUrl($service, Carbon::yesterday()))->assertJsonValidationErrors(['date']);
    $this->getJson(slotsUrl($service, Carbon::today()->addDays(61)))->assertJsonValidationErrors(['date']);

    [, $otherService] = professionalWithService();
    $this->getJson("/api/v1/professionals/{$professionalId}/slots?date={$date->toDateString()}&service_id={$otherService->id}")
        ->assertJsonValidationErrors(['service_id']);

    $service->update(['is_active' => false]);
    $this->getJson(slotsUrl($service, $date))->assertJsonValidationErrors(['service_id']);
});

it('returns 404 for a professional who is not publicly listed', function () {
    $pending = User::factory()->professional()->create();

    $this->getJson("/api/v1/professionals/{$pending->id}/slots?date=".Carbon::tomorrow()->toDateString().'&service_id=1')
        ->assertNotFound();
});
