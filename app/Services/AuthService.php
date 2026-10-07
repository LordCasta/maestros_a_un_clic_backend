<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuthService
{
    public function __construct(private readonly UploadService $uploads) {}

    /**
     * @param  array<string, mixed>  $data  Datos validados por RegisterClientRequest.
     */
    public function registerClient(array $data): User
    {
        return $this->withAvatar($data['avatar'] ?? null, function (?string $avatarPath) use ($data) {
            $user = User::create([
                ...Arr::only($data, ['name', 'email', 'password', 'phone', 'commune_id', 'address', 'latitude', 'longitude']),
                'role' => Role::Client,
                'avatar_path' => $avatarPath,
            ]);

            $user->clientProfile()->create(['birth_date' => $data['birth_date'] ?? null]);

            // refresh() trae los valores por defecto de la BD (verification_status, rating_*).
            return $user->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data  Datos validados por RegisterProfessionalRequest.
     */
    public function registerProfessional(array $data): User
    {
        return $this->withAvatar($data['avatar'] ?? null, function (?string $avatarPath) use ($data) {
            $user = User::create([
                ...Arr::only($data, ['name', 'email', 'password', 'phone', 'commune_id', 'address', 'latitude', 'longitude']),
                'role' => Role::Professional,
                'avatar_path' => $avatarPath,
            ]);

            $profile = $user->professionalProfile()->create(
                Arr::only($data, ['description', 'experience_years', 'hourly_rate']),
            );
            $profile->categories()->sync($data['category_ids']);

            return $user->refresh();
        });
    }

    /**
     * Devuelve el token, o null si las credenciales no son válidas.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        if (! Auth::guard('web')->once($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        abort_if($user->isBlocked(), 403, 'Tu cuenta está bloqueada. Contacta a soporte.');

        return [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }

    /**
     * Crea el usuario en una transacción. Si algo falla, borra el avatar ya subido
     * para no dejar archivos huérfanos.
     *
     * @param  callable(?string): User  $create
     */
    private function withAvatar(?UploadedFile $avatar, callable $create): User
    {
        $avatarPath = $avatar ? $this->uploads->storePublic($avatar, 'avatars') : null;

        try {
            return DB::transaction(fn () => $create($avatarPath));
        } catch (Throwable $e) {
            if ($avatarPath) {
                $this->uploads->deletePublic($avatarPath);
            }

            throw $e;
        }
    }
}
