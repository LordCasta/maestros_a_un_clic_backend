<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\Commune;
use App\Models\User;

function professionalPayload(array $overrides = []): array
{
    $commune = Commune::firstOrCreate(
        ['code' => '11'],
        ['name' => 'Laureles-Estadio', 'type' => 'comuna', 'latitude' => 6.25, 'longitude' => -75.59],
    );

    return [
        'name' => 'Juan Castaño',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'commune_id' => $commune->id,
        'description' => 'Plomero con más de diez años de experiencia en instalaciones, reparaciones y mantenimiento del hogar.',
        'experience_years' => 10,
        'hourly_rate' => 45000,
        'category_ids' => Category::factory()->count(2)->create()->pluck('id')->all(),
        ...$overrides,
    ];
}

it('registers a professional with profile and categories', function () {
    $payload = professionalPayload();

    $this->postJson('/api/v1/auth/register/professional', $payload)
        ->assertCreated()
        ->assertJsonPath('data.user.role', 'professional')
        ->assertJsonPath('data.user.verification_status', 'unverified');

    $user = User::where('email', 'juan@example.com')->firstOrFail();
    $profile = $user->professionalProfile;

    expect($user->role)->toBe(Role::Professional)
        ->and($profile->experience_years)->toBe(10)
        ->and((float) $profile->hourly_rate)->toBe(45000.0)
        ->and($profile->categories->pluck('id')->all())->toEqualCanonicalizing($payload['category_ids']);
});

it('only accepts root categories as specialties', function () {
    $subcategory = Category::factory()->create(['parent_id' => Category::factory()]);

    $this->postJson('/api/v1/auth/register/professional', professionalPayload(['category_ids' => [$subcategory->id]]))
        ->assertJsonValidationErrors(['category_ids.0']);
});

it('requires a positive hourly rate', function () {
    $this->postJson('/api/v1/auth/register/professional', professionalPayload(['hourly_rate' => 0]))
        ->assertJsonValidationErrors(['hourly_rate']);
});

it('validates required fields for professional registration', function () {
    $this->postJson('/api/v1/auth/register/professional', ['email' => 'invalid-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name', 'email', 'password', 'commune_id', 'description', 'experience_years', 'hourly_rate', 'category_ids',
        ]);
});
