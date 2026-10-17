<?php

use App\Enums\CommuneType;
use App\Models\Category;
use App\Models\Commune;
use App\Models\PortfolioItem;
use App\Models\ProfessionalService;
use App\Models\User;

it('lists only verified and non blocked professionals', function () {
    $visible = User::factory()->professional()->verified()->create();
    User::factory()->professional()->create();
    User::factory()->professional()->verified()->blocked()->create();
    User::factory()->client()->verified()->create();

    $this->getJson('/api/v1/professionals')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visible->id)
        ->assertJsonPath('meta.total', 1);
});

it('does not expose private contact data', function () {
    User::factory()->professional()->verified()->create();

    $professional = $this->getJson('/api/v1/professionals')->json('data.0');

    expect($professional)->not->toHaveKeys(['email', 'phone', 'address', 'latitude', 'longitude']);
});

it('filters by root category and by service subcategory', function () {
    $plumbing = Category::factory()->create();
    $leaks = Category::factory()->create(['parent_id' => $plumbing->id]);

    $bySpecialty = User::factory()->professional()->verified()->create();
    $bySpecialty->professionalProfile->categories()->attach($plumbing);

    [$byService] = professionalWithService(['category_id' => $leaks->id]);
    User::factory()->professional()->verified()->create();

    $this->getJson("/api/v1/professionals?category_id={$plumbing->id}")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $bySpecialty->id);

    $this->getJson("/api/v1/professionals?category_id={$leaks->id}")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $byService->id);
});

it('sorts by price', function () {
    $cheap = User::factory()->professional()->verified()->create();
    $cheap->professionalProfile->update(['hourly_rate' => 30000]);
    $expensive = User::factory()->professional()->verified()->create();
    $expensive->professionalProfile->update(['hourly_rate' => 90000]);

    $this->getJson('/api/v1/professionals?sort=price_asc')->assertJsonPath('data.0.id', $cheap->id);
    $this->getJson('/api/v1/professionals?sort=price_desc')->assertJsonPath('data.0.id', $expensive->id);
});

it('shows a professional with active services and portfolio', function () {
    [$professional, $active] = professionalWithService();
    ProfessionalService::factory()->inactive()->for($professional->professionalProfile)->create();
    PortfolioItem::create(['professional_profile_id' => $professional->professionalProfile->id, 'image_path' => 'portfolio/a.jpg']);

    $this->getJson("/api/v1/professionals/{$professional->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.services')
        ->assertJsonPath('data.services.0.id', $active->id)
        ->assertJsonCount(1, 'data.portfolio');
});

it('returns 404 for a professional that is not verified', function () {
    $professional = User::factory()->professional()->create();

    $this->getJson("/api/v1/professionals/{$professional->id}")->assertNotFound();
});

it('filters professionals within their service radius and sorts by proximity', function () {
    $clientLat = 6.2080000;
    $clientLng = -75.5670000;

    $close = User::factory()->professional()->verified()->create([
        'latitude' => 6.2150000,
        'longitude' => -75.5670000,
    ]);
    $close->professionalProfile->update(['service_radius_km' => 5]);

    $medium = User::factory()->professional()->verified()->create([
        'latitude' => 6.2800000,
        'longitude' => -75.5670000,
    ]);
    $medium->professionalProfile->update(['service_radius_km' => 15]);

    $farWithSmallRadius = User::factory()->professional()->verified()->create([
        'latitude' => 6.2800000,
        'longitude' => -75.5670000,
    ]);
    $farWithSmallRadius->professionalProfile->update(['service_radius_km' => 3]);

    $response = $this->getJson("/api/v1/professionals?lat={$clientLat}&lng={$clientLng}")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($response->json('data.0.id'))->toBe($close->id)
        ->and($response->json('data.0.distance_km'))->toBeFloat()
        ->and($response->json('data.1.id'))->toBe($medium->id)
        ->and($response->json('data.1.distance_km'))->toBeGreaterThan($response->json('data.0.distance_km'));
});

it('validates coordinates and sort by distance', function () {
    $this->getJson('/api/v1/professionals?sort=distance')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lat', 'lng']);

    $this->getJson('/api/v1/professionals?lat=6.25')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lng']);

    $this->getJson('/api/v1/professionals?lat=100&lng=-75.56')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lat']);
});

it('suggests professionals from neighboring communes when the requested commune has none', function () {
    $communeA = Commune::create([
        'name' => 'Comuna A',
        'code' => 'CA',
        'type' => CommuneType::Comuna,
        'latitude' => 6.2500000,
        'longitude' => -75.5600000,
    ]);
    $communeB = Commune::create([
        'name' => 'Comuna B',
        'code' => 'CB',
        'type' => CommuneType::Comuna,
        'latitude' => 6.2600000,
        'longitude' => -75.5600000,
    ]);

    $communeA->neighbors()->attach($communeB);

    $neighborPro = User::factory()->professional()->verified()->create([
        'commune_id' => $communeB->id,
        'latitude' => 6.2600000,
        'longitude' => -75.5600000,
    ]);

    $response = $this->getJson("/api/v1/professionals?commune_id={$communeA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $neighborPro->id)
        ->assertJsonPath('data.0.is_suggested', true)
        ->assertJsonPath('meta.suggested_from_neighbors', true);

    expect($response->json('data.0.distance_km'))->not->toBeNull();
});
