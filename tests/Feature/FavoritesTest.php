<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lets a client add, list and remove a favorite professional', function () {
    $professional = User::factory()->professional()->verified()->create();
    Sanctum::actingAs(User::factory()->client()->create());

    $this->postJson("/api/v1/favorites/{$professional->id}")
        ->assertCreated()
        ->assertJsonPath('message', 'Agregado a favoritos.')
        ->assertJsonPath('data.professional.id', $professional->id);

    // Agregar dos veces no duplica.
    $this->postJson("/api/v1/favorites/{$professional->id}")->assertCreated();

    $this->getJson('/api/v1/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.professional.id', $professional->id);

    $this->deleteJson("/api/v1/favorites/{$professional->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Eliminado de favoritos.');

    $this->assertDatabaseCount('favorites', 0);
});

it('only allows publicly listed professionals as favorites', function () {
    $unverified = User::factory()->professional()->create();
    $client = User::factory()->client()->create();
    Sanctum::actingAs($client);

    $this->postJson("/api/v1/favorites/{$unverified->id}")->assertNotFound();
    $this->postJson("/api/v1/favorites/{$client->id}")->assertNotFound();
});

it('is only available to clients', function () {
    Sanctum::actingAs(User::factory()->professional()->verified()->create());

    $this->getJson('/api/v1/favorites')->assertForbidden();
});
