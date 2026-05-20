<?php

use App\Models\ProfessionalProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('registers a professional with specialties certificates and portfolio files', function () {
    Storage::fake('public');

    $specialties = Specialty::factory()->count(2)->create();

    $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/auth/register/professional', [
        'name' => 'Profesional Prueba',
        'email' => 'profesional@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+56911112222',
        'commune' => 'Santiago Centro',
        'latitude' => -33.4489,
        'longitude' => -70.6693,
        'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
        'specialties' => $specialties->pluck('id')->all(),
        'hourly_rate' => 25000,
        'experience_years' => 7,
        'description' => 'Profesional con más de siete años de experiencia realizando trabajos de alta calidad en el hogar y ofreciendo atención responsable y puntual.',
        'certificates' => [
            UploadedFile::fake()->create('certificado-1.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->create('certificado-2.pdf', 200, 'application/pdf'),
        ],
        'portfolio_images' => [
            UploadedFile::fake()->image('portfolio-1.jpg'),
            UploadedFile::fake()->image('portfolio-2.jpg'),
        ],
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Profesional registrado',
        ])
        ->assertJsonPath('data.user.role', 'professional')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'avatar',
                    'role',
                    'commune',
                    'is_verified',
                    'created_at',
                ],
                'token',
            ],
        ]);

    $user = User::where('email', 'profesional@example.com')->firstOrFail();
    $profile = ProfessionalProfile::where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('users', [
        'email' => 'profesional@example.com',
        'role' => 'professional',
        'commune' => 'Santiago Centro',
    ]);

    $this->assertDatabaseHas('professional_profiles', [
        'user_id' => $user->id,
        'experience_years' => 7,
    ]);

    foreach ($specialties as $specialty) {
        $this->assertDatabaseHas('professional_specialty', [
            'professional_profile_id' => $profile->id,
            'specialty_id' => $specialty->id,
        ]);
    }

    $this->assertDatabaseCount('certificates', 2);
    $this->assertDatabaseCount('portfolio_items', 2);

    Storage::disk('public')->assertExists($user->avatar);

    foreach ($profile->certificates as $certificate) {
        Storage::disk('public')->assertExists($certificate->file_path);
    }

    foreach ($profile->portfolioItems as $item) {
        Storage::disk('public')->assertExists($item->image_path);
    }
});

it('validates required fields for professional registration', function () {
    $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/auth/register/professional', [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'specialties',
            'experience_years',
            'description',
        ]);
});


