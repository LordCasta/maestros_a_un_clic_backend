<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlockUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * HU038: listado de usuarios con filtros para moderación y administración.
     */
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $status = $request->query('status'); // 'blocked', 'active'
        $q = $request->query('q');

        $paginator = User::query()
            ->with(['commune', 'blockedBy.commune'])
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when($status === 'blocked', fn (Builder $query) => $query->whereNotNull('blocked_at'))
            ->when($status === 'active', fn (Builder $query) => $query->whereNull('blocked_at'))
            ->when($q, function (Builder $query, string $term) {
                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(15);

        return $this->paginated(AdminUserResource::collection($paginator), $paginator);
    }

    /**
     * HU038: bloquear a un usuario problemático.
     */
    public function block(BlockUserRequest $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'No puedes bloquear tu propia cuenta.');
        }

        if ($user->role === Role::Admin) {
            abort(403, 'No se puede bloquear a un administrador.');
        }

        $user->update([
            'blocked_at' => now(),
            'blocked_reason' => $request->validated('reason'),
            'blocked_by' => $request->user()->id,
        ]);

        $user->tokens()->delete();
        $user->load(['commune', 'blockedBy']);

        return $this->ok(new AdminUserResource($user), 'Usuario bloqueado correctamente.');
    }

    /**
     * HU038: desbloquear a un usuario.
     */
    public function unblock(Request $request, User $user): JsonResponse
    {
        $user->update([
            'blocked_at' => null,
            'blocked_reason' => null,
            'blocked_by' => null,
        ]);

        $user->load(['commune', 'blockedBy']);

        return $this->ok(new AdminUserResource($user), 'Usuario desbloqueado correctamente.');
    }
}
