<?php

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists a client favorites and can add and remove a professional', function () {
    $client = User::factory()->create(['role' => 'client']);
    $professional = User::factory()->create(['role' => 'professional']);

    $token = $client->createToken('api-token')->plainTextToken;

    $storeResponse = $this->withToken($token)->postJson('/api/favorites/'.$professional->id);

    $storeResponse->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Agregado a favoritos',
        ])
        ->assertJsonPath('data.professional.id', $professional->id);

    $this->assertDatabaseHas('favorites', [
        'client_id' => $client->id,
        'professional_id' => $professional->id,
    ]);

    $indexResponse = $this->withToken($token)->getJson('/api/favorites');

    $indexResponse->assertOk()
        ->assertJsonPath('data.0.professional.id', $professional->id);

    $destroyResponse = $this->withToken($token)->deleteJson('/api/favorites/'.$professional->id);

    $destroyResponse->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Eliminado de favoritos',
        ]);

    $this->assertDatabaseMissing('favorites', [
        'client_id' => $client->id,
        'professional_id' => $professional->id,
    ]);
});

it('prevents adding a non professional to favorites', function () {
    $client = User::factory()->create(['role' => 'client']);
    $notProfessional = User::factory()->create(['role' => 'client']);

    $token = $client->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/favorites/'.$notProfessional->id);

    $response->assertNotFound();
});

