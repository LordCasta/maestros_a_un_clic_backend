<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Commune;
use App\Models\PortfolioItem;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Profesional con la especialidad "Plomería" y su subcategoría "Fugas".
 *
 * @return array{0: User, 1: Category, 2: Category}
 */
function plumber(): array
{
    $professional = User::factory()->professional()->verified()->create();
    $specialty = Category::factory()->create(['name' => 'Plomería']);
    $subcategory = Category::factory()->create(['parent_id' => $specialty->id, 'name' => 'Fugas']);
    $professional->professionalProfile->categories()->sync([$specialty->id]);

    return [$professional, $specialty, $subcategory];
}

function servicePayload(int $categoryId, array $overrides = []): array
{
    return $overrides + [
        'category_id' => $categoryId,
        'title' => 'Reparación de fugas',
        'description' => 'Incluye revisión de tuberías.',
        'price_type' => 'fixed',
        'price' => 80000,
        'estimated_duration_minutes' => 90,
    ];
}

describe('profile', function () {
    it('shows the own profile with private location data', function () {
        [$professional] = plumber();
        $professional->update(['address' => 'Calle 10 # 43-20', 'latitude' => 6.2442, 'longitude' => -75.5812]);
        Sanctum::actingAs($professional);

        $this->getJson('/api/v1/professional/profile')
            ->assertOk()
            ->assertJsonPath('data.address', 'Calle 10 # 43-20')
            ->assertJsonPath('data.latitude', 6.2442)
            ->assertJsonPath('data.categories.0.name', 'Plomería');
    });

    it('updates only the sent fields and keeps the rest', function () {
        [$professional] = plumber();
        $professional->professionalProfile->update(['description' => 'Plomero con diez años.', 'experience_years' => 10]);
        $commune = Commune::create(['name' => 'Laureles-Estadio', 'code' => '11', 'type' => 'comuna', 'latitude' => 6.2453, 'longitude' => -75.5946]);
        Sanctum::actingAs($professional);

        $this->patchJson('/api/v1/professional/profile', [
            'hourly_rate' => 50000,
            'commune_id' => $commune->id,
            'latitude' => 6.25,
            'longitude' => -75.56,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Perfil actualizado.')
            ->assertJsonPath('data.hourly_rate', 50000)
            ->assertJsonPath('data.description', 'Plomero con diez años.')
            ->assertJsonPath('data.experience_years', 10)
            ->assertJsonPath('data.commune.id', $commune->id);

        // La ubicación vive en users: es la que usa la búsqueda por cercanía.
        expect($professional->refresh())
            ->commune_id->toBe($commune->id)
            ->latitude->toBe(6.25)
            ->and($professional->verification_status->value)->toBe('approved');
    });

    it('requires an hourly rate greater than zero', function () {
        [$professional] = plumber();
        Sanctum::actingAs($professional);

        $this->patchJson('/api/v1/professional/profile', ['hourly_rate' => 0])->assertJsonValidationErrors(['hourly_rate']);
        $this->patchJson('/api/v1/professional/profile', ['hourly_rate' => 'mucho'])->assertJsonValidationErrors(['hourly_rate']);
        $this->patchJson('/api/v1/professional/profile', ['latitude' => 6.2])->assertJsonValidationErrors(['longitude']);
    });

    it('is only for professionals', function () {
        $this->patchJson('/api/v1/professional/profile', [])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->client()->verified()->create());
        $this->getJson('/api/v1/professional/profile')->assertForbidden();
    });
});

describe('specialties', function () {
    it('adds and removes specialties from the catalog', function () {
        [$professional, $plumbing] = plumber();
        $electricity = Category::factory()->create(['name' => 'Electricidad']);
        Sanctum::actingAs($professional);

        $this->putJson('/api/v1/professional/categories', ['category_ids' => [$electricity->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonPath('data.categories.0.name', 'Electricidad');

        $this->putJson('/api/v1/professional/categories', ['category_ids' => []])->assertJsonValidationErrors(['category_ids']);

        $sub = Category::factory()->create(['parent_id' => $plumbing->id]);
        $this->putJson('/api/v1/professional/categories', ['category_ids' => [$sub->id]])->assertJsonValidationErrors(['category_ids.0']);
    });

    it('does not remove a specialty with active services', function () {
        [$professional, , $leaks] = plumber();
        ProfessionalService::factory()->for($professional->professionalProfile)->create(['category_id' => $leaks->id]);
        $other = Category::factory()->create();
        Sanctum::actingAs($professional);

        $this->putJson('/api/v1/professional/categories', ['category_ids' => [$other->id]])
            ->assertJsonValidationErrors(['category_ids' => 'Tienes servicios activos en Plomería. Desactívalos antes de quitar la especialidad.']);
    });
});

describe('services', function () {
    it('creates a service with its estimated duration in one of the specialties', function () {
        [$professional, , $leaks] = plumber();
        Sanctum::actingAs($professional);

        $this->postJson('/api/v1/professional/services', servicePayload($leaks->id))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Reparación de fugas')
            ->assertJsonPath('data.estimated_duration_minutes', 90)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.category.name', 'Fugas');

        // Se ve en el perfil público con su duración (HU047).
        $this->getJson("/api/v1/professionals/{$professional->id}")
            ->assertJsonPath('data.services.0.estimated_duration_minutes', 90);
    });

    it('validates the service', function () {
        [$professional, $plumbing] = plumber();
        Sanctum::actingAs($professional);

        $this->postJson('/api/v1/professional/services', servicePayload($plumbing->id, [
            'price' => 0,
            'estimated_duration_minutes' => 5,
            'price_type' => 'gratis',
            'title' => '',
        ]))->assertJsonValidationErrors(['category_id', 'price', 'estimated_duration_minutes', 'price_type', 'title']);

        $foreign = Category::factory()->create(['parent_id' => Category::factory()->create()->id]);
        $this->postJson('/api/v1/professional/services', servicePayload($foreign->id))
            ->assertJsonValidationErrors(['category_id' => 'Agrega primero esa especialidad a tu perfil.']);
    });

    it('lists own services including inactive ones', function () {
        [$professional, , $leaks] = plumber();
        ProfessionalService::factory()->for($professional->professionalProfile)->create(['category_id' => $leaks->id, 'is_active' => false]);
        ProfessionalService::factory()->create();
        Sanctum::actingAs($professional);

        $this->getJson('/api/v1/professional/services')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', false);
    });

    it('updates and deactivates a service without changing existing bookings', function () {
        [$professional, , $leaks] = plumber();
        $service = ProfessionalService::factory()->for($professional->professionalProfile)->create(['category_id' => $leaks->id, 'price' => 80000]);
        $booking = Booking::factory()->status(BookingStatus::Confirmed)->for($service, 'service')->create();
        Sanctum::actingAs($professional);

        $this->patchJson("/api/v1/professional/services/{$service->id}", ['price' => 95000, 'estimated_duration_minutes' => 120, 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.price', 95000)
            ->assertJsonPath('data.estimated_duration_minutes', 120)
            ->assertJsonPath('data.is_active', false);

        expect((float) $booking->refresh()->agreed_price)->toBe(80000.0);
    });

    it('deletes a service without bookings but not one with bookings', function () {
        [$professional, , $leaks] = plumber();
        $unused = ProfessionalService::factory()->for($professional->professionalProfile)->create(['category_id' => $leaks->id]);
        $used = ProfessionalService::factory()->for($professional->professionalProfile)->create(['category_id' => $leaks->id]);
        Booking::factory()->for($used, 'service')->create();
        Sanctum::actingAs($professional);

        $this->deleteJson("/api/v1/professional/services/{$unused->id}")->assertOk();
        $this->assertModelMissing($unused);

        $this->deleteJson("/api/v1/professional/services/{$used->id}")->assertConflict();
        $this->assertModelExists($used);
    });

    it('only lets the owner change a service', function () {
        $service = ProfessionalService::factory()->create();
        [$other] = plumber();
        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/professional/services/{$service->id}", ['price' => 1000])->assertForbidden();
        $this->deleteJson("/api/v1/professional/services/{$service->id}")->assertForbidden();
    });
});

describe('portfolio', function () {
    it('uploads photos that show in the public gallery and deletes them', function () {
        Storage::fake('public');
        [$professional] = plumber();
        Sanctum::actingAs($professional);

        $id = $this->postJson('/api/v1/professional/portfolio', [
            'image' => UploadedFile::fake()->image('baño.jpg', 800, 600),
            'description' => 'Baño remodelado',
        ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Baño remodelado')
            ->json('data.id');

        $item = PortfolioItem::findOrFail($id);
        Storage::disk('public')->assertExists($item->image_path);
        $this->getJson("/api/v1/professionals/{$professional->id}")->assertJsonCount(1, 'data.portfolio');

        $this->deleteJson("/api/v1/professional/portfolio/{$id}")->assertOk();
        Storage::disk('public')->assertMissing($item->image_path);
        $this->getJson('/api/v1/professional/portfolio')->assertJsonCount(0, 'data');
    });

    it('validates the image and the limit of photos', function () {
        Storage::fake('public');
        [$professional] = plumber();
        Sanctum::actingAs($professional);

        $this->postJson('/api/v1/professional/portfolio', ['image' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertJsonValidationErrors(['image']);
        $this->postJson('/api/v1/professional/portfolio', ['image' => UploadedFile::fake()->image('enorme.jpg')->size(6000)])
            ->assertJsonValidationErrors(['image']);

        foreach (range(1, 12) as $n) {
            $professional->professionalProfile->portfolioItems()->create(['image_path' => "portfolio/{$n}.jpg"]);
        }
        $this->postJson('/api/v1/professional/portfolio', ['image' => UploadedFile::fake()->image('otra.jpg')])
            ->assertJsonValidationErrors(['image']);
    });

    it('only lets the owner delete a photo', function () {
        Storage::fake('public');
        $item = User::factory()->professional()->verified()->create()->professionalProfile->portfolioItems()->create(['image_path' => 'portfolio/a.jpg']);
        [$other] = plumber();
        Sanctum::actingAs($other);

        $this->deleteJson("/api/v1/professional/portfolio/{$item->id}")->assertForbidden();
    });
});
