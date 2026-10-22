<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * HU019, HU022, HU023, HU031, HU032: la cuenta del usuario autenticado.
 */
class AccountService
{
    /** Nombre que ve la otra parte en reservas, reseñas y chats de una cuenta eliminada. */
    public const DELETED_NAME = 'Usuario eliminado';

    public function __construct(private readonly UploadService $uploads) {}

    /**
     * HU022, HU031: datos personales y ubicación manual.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->load('commune');
    }

    /**
     * HU032: cambia la foto de perfil y borra la anterior.
     */
    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        $previous = $user->avatar_path;

        $user->update(['avatar_path' => $this->uploads->storePublic($avatar, 'avatars')]);

        if ($previous) {
            $this->uploads->deletePublic($previous);
        }

        return $user->load('commune');
    }

    /**
     * HU019: la contraseña actual ya la valida el FormRequest.
     */
    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    /**
     * HU023 (decisión D12): soft delete con anonimización. La fila se conserva para que la otra
     * parte no pierda sus reservas, reseñas y chats, pero ya no identifica a la persona
     * (Ley 1581 de 2012, derecho de supresión): se borran sus datos de contacto, ubicación,
     * fotos, documentos de verificación, favoritos, notificaciones y sesiones.
     * No se puede con reservas activas o pendientes: primero hay que terminarlas o cancelarlas.
     */
    public function delete(User $user): void
    {
        $hasActiveBookings = Booking::query()
            ->where(fn (Builder $q) => $q->where('client_id', $user->id)->orWhere('professional_id', $user->id))
            ->occupyingSchedule()
            ->exists();

        abort_if($hasActiveBookings, 422, 'No puedes eliminar tu cuenta mientras tengas reservas activas o pendientes.');

        $publicFiles = array_filter([$user->avatar_path]);
        $privateFiles = [];

        DB::transaction(function () use ($user, &$publicFiles, &$privateFiles) {
            $user->tokens()->delete();
            $user->notifications()->delete();
            // Sus favoritos y los de otros clientes que lo tenían guardado (si es profesional).
            Favorite::where('client_id', $user->id)->orWhere('professional_id', $user->id)->delete();
            $user->clientProfile()->update(['birth_date' => null]);

            foreach ($user->verificationRequests()->with('documents')->get() as $request) {
                array_push($privateFiles, ...$request->documents->pluck('file_path'));
                $request->delete();
            }

            if ($profile = $user->professionalProfile) {
                array_push($publicFiles, ...$profile->portfolioItems()->pluck('image_path'));
                $profile->portfolioItems()->delete();
                $profile->availabilityRules()->delete();
                $profile->availabilityBlocks()->delete();
                // Los servicios se conservan porque las reservas pasadas los referencian.
                $profile->services()->update(['is_active' => false]);
                $profile->update(['description' => null]);
            }

            $user->forceFill([
                'name' => self::DELETED_NAME,
                'email' => "eliminado-{$user->id}@maestros.invalid",
                'password' => Str::random(64),
                'phone' => null,
                'avatar_path' => null,
                'commune_id' => null,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'remember_token' => null,
            ])->save();

            $user->delete();
        });

        // Los archivos se borran después de confirmar la transacción: si algo falla, no se pierden.
        $this->uploads->deletePublic(...$publicFiles);
        $this->uploads->deletePrivate(...$privateFiles);
    }
}
