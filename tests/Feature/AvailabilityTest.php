<?php

use App\Enums\BookingStatus;
use App\Events\AvailabilityChanged;
use App\Models\AvailabilityBlock;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

function weeklySchedule(array $overrides = []): array
{
    return $overrides + [
        'buffer_minutes' => 30,
        'rules' => [
            ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
            ['weekday' => 1, 'start_time' => '14:00', 'end_time' => '18:00'],
            ['weekday' => 6, 'start_time' => '09:00', 'end_time' => '13:00'],
        ],
    ];
}

describe('viewing the agenda', function () {
    it('shows the schedule, the rest time and the upcoming blocks of the professional', function () {
        $professional = User::factory()->professional()->verified()->create();
        $profile = $professional->professionalProfile;
        $profile->update(['buffer_minutes' => 15]);
        AvailabilityRule::create(['professional_profile_id' => $profile->id, 'weekday' => 2, 'start_time' => '08:00', 'end_time' => '17:00']);
        $upcoming = AvailabilityBlock::create(['professional_profile_id' => $profile->id, 'starts_at' => Carbon::tomorrow()->setTime(9, 0), 'ends_at' => Carbon::tomorrow()->setTime(11, 0), 'reason' => 'Cita médica']);
        AvailabilityBlock::create(['professional_profile_id' => $profile->id, 'starts_at' => Carbon::yesterday()->setTime(9, 0), 'ends_at' => Carbon::yesterday()->setTime(11, 0)]);

        // Datos de otro profesional que no deben aparecer.
        $other = User::factory()->professional()->verified()->create();
        AvailabilityRule::create(['professional_profile_id' => $other->professionalProfile->id, 'weekday' => 3, 'start_time' => '08:00', 'end_time' => '09:00']);

        Sanctum::actingAs($professional);

        $this->getJson('/api/v1/professional/availability')
            ->assertOk()
            ->assertJsonPath('data.buffer_minutes', 15)
            ->assertJsonCount(1, 'data.rules')
            ->assertJsonPath('data.rules.0', fn (array $rule) => $rule['weekday'] === 2 && $rule['start_time'] === '08:00' && $rule['end_time'] === '17:00')
            ->assertJsonCount(1, 'data.blocks')
            ->assertJsonPath('data.blocks.0.id', $upcoming->id)
            ->assertJsonPath('data.blocks.0.reason', 'Cita médica');
    });

    it('is only for professionals', function () {
        $this->getJson('/api/v1/professional/availability')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->client()->verified()->create());
        $this->getJson('/api/v1/professional/availability')->assertForbidden();
    });
});

describe('updating the weekly schedule', function () {
    it('replaces the schedule and the rest time and announces the change', function () {
        Event::fake([AvailabilityChanged::class]);
        $professional = User::factory()->professional()->verified()->create();
        $profile = $professional->professionalProfile;
        AvailabilityRule::create(['professional_profile_id' => $profile->id, 'weekday' => 0, 'start_time' => '08:00', 'end_time' => '10:00']);
        Sanctum::actingAs($professional);

        $this->putJson('/api/v1/professional/availability/rules', weeklySchedule())
            ->assertOk()
            ->assertJsonPath('message', 'Horario guardado.')
            ->assertJsonPath('data.buffer_minutes', 30)
            ->assertJsonCount(3, 'data.rules');

        expect($profile->availabilityRules()->where('weekday', 0)->exists())->toBeFalse()
            ->and($profile->refresh()->buffer_minutes)->toBe(30);
        Event::assertDispatched(AvailabilityChanged::class, fn ($event) => $event->professionalId === $professional->id);
    });

    it('accepts an empty schedule', function () {
        $professional = User::factory()->professional()->verified()->create();
        Sanctum::actingAs($professional);

        $this->putJson('/api/v1/professional/availability/rules', ['buffer_minutes' => 0, 'rules' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.rules');
    });

    it('validates times, days and rest time', function () {
        Sanctum::actingAs(User::factory()->professional()->verified()->create());

        $this->putJson('/api/v1/professional/availability/rules', [
            'buffer_minutes' => 500,
            'rules' => [
                ['weekday' => 7, 'start_time' => '08:00', 'end_time' => '12:00'],
                ['weekday' => 1, 'start_time' => '12:00', 'end_time' => '08:00'],
                ['weekday' => 2, 'start_time' => '8am', 'end_time' => '12:00'],
            ],
        ])->assertJsonValidationErrors(['buffer_minutes', 'rules.0.weekday', 'rules.1.end_time', 'rules.2.start_time']);

        $this->putJson('/api/v1/professional/availability/rules', [])->assertJsonValidationErrors(['buffer_minutes', 'rules']);
    });

    it('rejects two ranges of the same day that overlap', function () {
        Sanctum::actingAs(User::factory()->professional()->verified()->create());

        $this->putJson('/api/v1/professional/availability/rules', weeklySchedule(['rules' => [
            ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
            ['weekday' => 2, 'start_time' => '10:00', 'end_time' => '14:00'],
            ['weekday' => 1, 'start_time' => '11:00', 'end_time' => '14:00'],
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rules.2.start_time' => 'Esta franja se cruza con otra del mismo día.'])
            ->assertJsonMissingValidationErrors(['rules.1.start_time']);
    });

    it('makes the new schedule the one used for free times', function () {
        $professional = User::factory()->professional()->verified()->create();
        $service = $professional->professionalProfile->services()->create([
            'category_id' => Category::factory()->create()->id,
            'title' => 'Revisión',
            'price_type' => 'fixed',
            'price' => 50000,
            'estimated_duration_minutes' => 60,
        ]);
        $date = Carbon::tomorrow();
        Sanctum::actingAs($professional);

        $this->putJson('/api/v1/professional/availability/rules', ['buffer_minutes' => 0, 'rules' => [
            ['weekday' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00'],
        ]])->assertOk();

        $times = collect($this->getJson("/api/v1/professionals/{$professional->id}/slots?date={$date->toDateString()}&service_id={$service->id}")->json('data.slots'))
            ->map(fn (array $slot) => Carbon::parse($slot['starts_at'])->format('H:i'))
            ->all();

        expect($times)->toBe(['09:00', '09:30', '10:00']);
    });
});

describe('blocking time', function () {
    it('blocks an interval and announces the change', function () {
        Event::fake([AvailabilityChanged::class]);
        $professional = User::factory()->professional()->verified()->create();
        Sanctum::actingAs($professional);

        $this->postJson('/api/v1/professional/availability/blocks', [
            'starts_at' => Carbon::tomorrow()->setTime(9, 0)->toIso8601String(),
            'ends_at' => Carbon::tomorrow()->setTime(12, 0)->toIso8601String(),
            'reason' => 'Cita médica',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Horas bloqueadas.')
            ->assertJsonPath('data.reason', 'Cita médica')
            ->assertJsonPath('data.starts_at', Carbon::tomorrow()->setTime(9, 0)->toIso8601String());

        expect($professional->professionalProfile->availabilityBlocks()->count())->toBe(1);
        Event::assertDispatched(AvailabilityChanged::class);
    });

    it('does not block over an active booking', function () {
        $booking = Booking::factory()->create([
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
        ]);
        Booking::factory()->status(BookingStatus::Cancelled)->for($booking->service, 'service')->create([
            'starts_at' => Carbon::tomorrow()->setTime(14, 0),
            'ends_at' => Carbon::tomorrow()->setTime(15, 0),
        ]);
        Sanctum::actingAs($booking->professional);

        $this->postJson('/api/v1/professional/availability/blocks', [
            'starts_at' => Carbon::tomorrow()->setTime(9, 0)->toIso8601String(),
            'ends_at' => Carbon::tomorrow()->setTime(10, 30)->toIso8601String(),
        ])->assertConflict();

        // Una reserva cancelada no ocupa agenda.
        $this->postJson('/api/v1/professional/availability/blocks', [
            'starts_at' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String(),
            'ends_at' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String(),
        ])->assertCreated();
    });

    it('validates the interval', function () {
        Sanctum::actingAs(User::factory()->professional()->verified()->create());

        $this->postJson('/api/v1/professional/availability/blocks', [
            'starts_at' => Carbon::yesterday()->toIso8601String(),
            'ends_at' => Carbon::yesterday()->subHour()->toIso8601String(),
        ])->assertJsonValidationErrors(['starts_at', 'ends_at']);
    });

    it('removes a block of the professional only', function () {
        Event::fake([AvailabilityChanged::class]);
        $professional = User::factory()->professional()->verified()->create();
        $block = AvailabilityBlock::create([
            'professional_profile_id' => $professional->professionalProfile->id,
            'starts_at' => Carbon::tomorrow()->setTime(9, 0),
            'ends_at' => Carbon::tomorrow()->setTime(10, 0),
        ]);

        Sanctum::actingAs(User::factory()->professional()->verified()->create());
        $this->deleteJson("/api/v1/professional/availability/blocks/{$block->id}")->assertForbidden();

        Sanctum::actingAs($professional);
        $this->deleteJson("/api/v1/professional/availability/blocks/{$block->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Bloqueo eliminado.');

        $this->assertModelMissing($block);
        Event::assertDispatched(AvailabilityChanged::class);
    });
});

describe('announcing changes to clients', function () {
    it('broadcasts on the public channel of the professional', function () {
        $event = new AvailabilityChanged(5);

        expect($event->broadcastOn()->name)->toBe('professional.5.availability')
            ->and($event->broadcastAs())->toBe('availability.changed')
            ->and($event->broadcastWith())->toBe(['professional_id' => 5]);
    });

    it('announces when a booking takes or frees time', function () {
        Event::fake([AvailabilityChanged::class]);
        $booking = Booking::factory()->create();
        Sanctum::actingAs($booking->professional);

        $this->postJson("/api/v1/bookings/{$booking->id}/accept")->assertOk();
        Event::assertNotDispatched(AvailabilityChanged::class); // pending → accepted sigue ocupando

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel", ['reason' => 'Se me presentó un imprevisto.'])->assertOk();
        Event::assertDispatched(AvailabilityChanged::class, fn ($event) => $event->professionalId === $booking->professional_id);
    });
});
