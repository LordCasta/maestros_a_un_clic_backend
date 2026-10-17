<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\ProfessionalProfile;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * HU009, HU047: el profesional mantiene su perfil, especialidades, servicios y portafolio.
 */
class ProfessionalProfileService
{
    /** Máximo de fotos en el portafolio. */
    public const PORTFOLIO_LIMIT = 12;

    /** Campos que viven en users (ubicación usada por la búsqueda, HU002). */
    private const USER_FIELDS = ['commune_id', 'address', 'latitude', 'longitude'];

    public function __construct(private readonly UploadService $uploads) {}

    /**
     * Actualiza solo los campos enviados: lo que no viene se conserva.
     *
     * @param  array<string, mixed>  $data  Validado por UpdateProfessionalProfileRequest.
     */
    public function updateProfile(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $user->update(Arr::only($data, self::USER_FIELDS));
            $user->professionalProfile->update(Arr::except($data, self::USER_FIELDS));
        });
    }

    /**
     * Reemplaza las especialidades (categorías raíz). No deja quitar una que tenga servicios activos.
     *
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(ProfessionalProfile $profile, array $categoryIds): void
    {
        $inUse = $profile->services()
            ->active()
            ->with('category.parent')
            ->get()
            ->map(fn (ProfessionalService $service) => $service->category->parent)
            ->filter()
            ->reject(fn (Category $specialty) => in_array($specialty->id, $categoryIds, true))
            ->unique('id');

        if ($inUse->isNotEmpty()) {
            throw ValidationException::withMessages([
                'category_ids' => 'Tienes servicios activos en '.$inUse->pluck('name')->join(', ', ' y ')
                    .'. Desactívalos antes de quitar la especialidad.',
            ]);
        }

        $profile->categories()->sync($categoryIds);
    }

    /**
     * @param  array<string, mixed>  $data  Validado por StoreProfessionalServiceRequest.
     */
    public function createService(ProfessionalProfile $profile, array $data): ProfessionalService
    {
        $this->ensureSpecialty($profile, $data['category_id']);

        return $profile->services()->create($data + ['is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $data  Validado por UpdateProfessionalServiceRequest.
     */
    public function updateService(ProfessionalService $service, array $data): ProfessionalService
    {
        if (isset($data['category_id'])) {
            $this->ensureSpecialty($service->professionalProfile, $data['category_id']);
        }

        // Las reservas guardan su propio precio y horario: cambiar el servicio no las altera.
        $service->update($data);

        return $service;
    }

    /**
     * Un servicio con reservas no se borra (se pierde el historial): se desactiva.
     */
    public function deleteService(ProfessionalService $service): void
    {
        abort_if(
            $service->bookings()->exists(),
            409,
            'Este servicio tiene reservas. Desactívalo para que no se pueda volver a reservar.',
        );

        $service->delete();
    }

    public function addPortfolioItem(ProfessionalProfile $profile, UploadedFile $image, ?string $description): PortfolioItem
    {
        if ($profile->portfolioItems()->count() >= self::PORTFOLIO_LIMIT) {
            throw ValidationException::withMessages([
                'image' => 'Puedes tener hasta '.self::PORTFOLIO_LIMIT.' fotos. Borra alguna para subir otra.',
            ]);
        }

        return $profile->portfolioItems()->create([
            'image_path' => $this->uploads->storePublic($image, 'portfolio'),
            'description' => $description,
        ]);
    }

    public function removePortfolioItem(PortfolioItem $item): void
    {
        $this->uploads->deletePublic($item->image_path);
        $item->delete();
    }

    /**
     * El servicio debe estar en una subcategoría de una de sus especialidades.
     */
    private function ensureSpecialty(ProfessionalProfile $profile, int $categoryId): void
    {
        $parentId = Category::whereKey($categoryId)->value('parent_id');

        if (! $profile->categories()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'category_id' => 'Agrega primero esa especialidad a tu perfil.',
            ]);
        }
    }
}
