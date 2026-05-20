<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a booking for the authenticated client', function () {
    $client = User::factory()->create(['role' => 'client']);
    $professional = User::factory()->create(['role' => 'professional']);

    $token = $client->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/bookings', [
        'professional_id' => $professional->id,
        'service_description' => 'Necesito instalación y revisión de grifería en cocina.',
        'scheduled_date' => now()->addDay()->toDateTimeString(),
        'total' => 45000,
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.professional_id', $professional->id)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('bookings', [
        'client_id' => $client->id,
        'professional_id' => $professional->id,
        'status' => 'pending',
    ]);
});

it('lists client bookings when authenticated as client', function () {
    $client = User::factory()->create(['role' => 'client']);
    $professional = User::factory()->create(['role' => 'professional']);

    Booking::create([
        'client_id' => $client->id,
        'professional_id' => $professional->id,
        'service_description' => 'Servicio de prueba',
        'scheduled_date' => now()->addDay(),
        'status' => 'confirmed',
        'total' => 50000,
    ]);

    $token = $client->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/bookings');

    $response->assertOk()
        ->assertJsonPath('data.0.client_id', $client->id)
        ->assertJsonPath('data.0.professional_id', $professional->id);
});

it('lists professional bookings when authenticated as professional', function () {
    $client = User::factory()->create(['role' => 'client']);
    $professional = User::factory()->create(['role' => 'professional']);

    Booking::create([
        'client_id' => $client->id,
        'professional_id' => $professional->id,
        'service_description' => 'Servicio de prueba profesional',
        'scheduled_date' => now()->addDay(),
        'status' => 'confirmed',
        'total' => 75000,
    ]);

    $token = $professional->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/bookings');

    $response->assertOk()
        ->assertJsonPath('data.0.professional_id', $professional->id)
        ->assertJsonPath('data.0.client_id', $client->id);
});

