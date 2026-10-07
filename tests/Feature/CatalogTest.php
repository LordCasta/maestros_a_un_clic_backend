<?php

use Database\Seeders\CategorySeeder;
use Database\Seeders\CommuneSeeder;

it('lists the communes of Medellín', function () {
    $this->seed(CommuneSeeder::class);

    $this->getJson('/api/v1/communes')
        ->assertOk()
        ->assertJsonCount(21, 'data')
        ->assertJsonPath('data.0.name', 'Popular')
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'type']]]);
});

it('lists root categories with their subcategories', function () {
    $this->seed(CategorySeeder::class);

    $response = $this->getJson('/api/v1/categories')->assertOk();

    $plumbing = collect($response->json('data'))->firstWhere('slug', 'plomeria');

    expect($response->json('data'))->toHaveCount(9)
        ->and($plumbing['children'])->not->toBeEmpty()
        ->and($plumbing['children'][0]['parent_id'])->toBe($plumbing['id']);
});
