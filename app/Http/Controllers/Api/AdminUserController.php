<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private readonly ModerationService $moderation) {}

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
        $user = $this->moderation->block($user, $request->user(), $request->validated('reason'));

        return $this->ok(new AdminUserResource($user), 'Usuario bloqueado correctamente.');
    }

    /**
     * HU038: desbloquear a un usuario.
     */
    public function unblock(User $user): JsonResponse
    {
        $user = $this->moderation->unblock($user);

        return $this->ok(new AdminUserResource($user), 'Usuario desbloqueado correctamente.');
    }
}
