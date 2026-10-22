<?php

use App\Enums\AlertType;
use App\Enums\BookingStatus;
use App\Enums\ReportStatus;
use App\Models\AdminAlert;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

it('creates a user report and dispatches an admin alert (HU024)', function () {
    $reporter = User::factory()->client()->create();
    $reported = User::factory()->professional()->create();

    Sanctum::actingAs($reporter);

    $response = $this->postJson('/api/v1/reports', [
        'reported_user_id' => $reported->id,
        'reason' => 'Incumplimiento de horario',
        'description' => 'El profesional nunca llegó al servicio acordado y no respondió llamadas.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.reason', 'Incumplimiento de horario')
        ->assertJsonPath('data.reported_user.id', $reported->id);

    expect(Report::where('reporter_id', $reporter->id)->where('reported_user_id', $reported->id)->exists())->toBeTrue()
        ->and(AdminAlert::where('user_id', $reported->id)->where('type', AlertType::Report)->exists())->toBeTrue();
});

it('prevents self-reporting (HU024)', function () {
    $user = User::factory()->client()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reports', [
        'reported_user_id' => $user->id,
        'reason' => 'Prueba',
        'description' => 'Descripción de prueba para auto reporte que debe fallar.',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reported_user_id']);
});

it('lists reports for admin and resolves a report with user block (HU024, HU038)', function () {
    $admin = User::factory()->admin()->create();
    $reporter = User::factory()->client()->create();
    $reported = User::factory()->professional()->create();

    $report = Report::create([
        'reporter_id' => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason' => 'Fraude',
        'description' => 'El usuario cobró por fuera de la plataforma y canceló el trabajo.',
        'status' => ReportStatus::Open,
    ]);

    Sanctum::actingAs($admin);

    $listResponse = $this->getJson('/api/v1/admin/reports');
    $listResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data', 'meta']);

    $resolveResponse = $this->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
        'status' => 'resolved',
        'resolution_notes' => 'Se verificaron los mensajes y se procede con el bloqueo.',
        'block_user' => true,
    ]);

    $resolveResponse->assertOk()
        ->assertJsonPath('data.status', 'resolved')
        ->assertJsonPath('data.resolution_notes', 'Se verificaron los mensajes y se procede con el bloqueo.');

    $reported->refresh();
    expect($reported->isBlocked())->toBeTrue();
});

it('allows admin to list, block and unblock users (HU038)', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->client()->create();

    Sanctum::actingAs($admin);

    $list = $this->getJson('/api/v1/admin/users?role=client');
    $list->assertOk()
        ->assertJsonPath('success', true);

    $block = $this->postJson("/api/v1/admin/users/{$user->id}/block", [
        'reason' => 'Comportamiento sospechoso reportado por múltiples clientes.',
    ]);

    $block->assertOk()
        ->assertJsonPath('data.is_blocked', true);

    $user->refresh();
    expect($user->isBlocked())->toBeTrue();

    $unblock = $this->postJson("/api/v1/admin/users/{$user->id}/unblock");
    $unblock->assertOk()
        ->assertJsonPath('data.is_blocked', false);

    $user->refresh();
    expect($user->isBlocked())->toBeFalse();
});

it('prevents admin from blocking themselves or other admins (HU038)', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $selfBlock = $this->postJson("/api/v1/admin/users/{$admin->id}/block", [
        'reason' => 'Prueba auto-bloqueo',
    ]);
    $selfBlock->assertStatus(422);

    $otherAdminBlock = $this->postJson("/api/v1/admin/users/{$otherAdmin->id}/block", [
        'reason' => 'Prueba bloqueo de admin',
    ]);
    $otherAdminBlock->assertForbidden();
});

it('lists and resolves system alerts (HU045)', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->professional()->create();

    $alert = AdminAlert::create([
        'user_id' => $user->id,
        'type' => AlertType::LowRating,
        'data' => ['rating' => 1, 'comment' => 'Pésimo servicio'],
    ]);

    Sanctum::actingAs($admin);

    $index = $this->getJson('/api/v1/admin/alerts?status=pending');
    $index->assertOk()
        ->assertJsonPath('success', true);

    $resolve = $this->postJson("/api/v1/admin/alerts/{$alert->id}/resolve");
    $resolve->assertOk()
        ->assertJsonPath('success', true);

    $alert->refresh();
    expect($alert->resolved_at)->not->toBeNull()
        ->and($alert->resolved_by)->toBe($admin->id);
});

it('returns client profile for booking interactions (HU044)', function () {
    $pro = User::factory()->professional()->create();
    $client = User::factory()->client()->create();

    Sanctum::actingAs($pro);

    $response = $this->getJson("/api/v1/clients/{$client->id}");
    $response->assertOk()
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.name', $client->name)
        ->assertJsonStructure(['data' => ['rating', 'completed_bookings_count', 'member_since']]);
});

it('returns public reviews of a professional (HU029)', function () {
    $pro = User::factory()->professional()->create();
    $client = User::factory()->client()->create();
    $booking = Booking::factory()->create([
        'client_id' => $client->id,
        'professional_id' => $pro->id,
        'status' => BookingStatus::Completed,
    ]);

    Review::create([
        'booking_id' => $booking->id,
        'reviewer_id' => $client->id,
        'reviewee_id' => $pro->id,
        'rating' => 5,
        'comment' => 'Excelente trabajo, muy puntual y pulcro.',
    ]);

    $response = $this->getJson("/api/v1/professionals/{$pro->id}/reviews");
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 5)
        ->assertJsonPath('data.0.comment', 'Excelente trabajo, muy puntual y pulcro.');
});

it('filters professionals by available_now (HU030)', function () {
    // Hora fija: con la hora real, de 10 p. m. a 2 a. m. la franja de ±2 h cruza la medianoche.
    Carbon::setTestNow(Carbon::parse('2026-10-21 10:00', config('app.timezone')));
    $now = Carbon::now(config('app.timezone'));
    $weekday = $now->dayOfWeek;
    $timeStart = $now->copy()->subHours(2)->format('H:i:s');
    $timeEnd = $now->copy()->addHours(2)->format('H:i:s');

    // Profesional disponible ahora
    $availablePro = User::factory()->professional()->verified()->create();
    AvailabilityRule::create([
        'professional_profile_id' => $availablePro->professionalProfile->id,
        'weekday' => $weekday,
        'start_time' => $timeStart,
        'end_time' => $timeEnd,
    ]);

    // Profesional no disponible (sin horario en este momento)
    $unavailablePro = User::factory()->professional()->verified()->create();
    AvailabilityRule::create([
        'professional_profile_id' => $unavailablePro->professionalProfile->id,
        'weekday' => ($weekday + 2) % 7,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $response = $this->getJson('/api/v1/professionals?available_now=1');
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($availablePro->id)
        ->and($ids)->not->toContain($unavailablePro->id);
});
