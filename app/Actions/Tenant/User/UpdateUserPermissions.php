<?php

namespace App\Actions\Tenant\User;

use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserPermissions
{
    public function __construct(private readonly RoleManagementService $roleManagementService) {}

    /**
     * @param  array<int, string>  $permissions
     */
    public function handle(User $user, array $permissions, User $actor): User
    {
        return DB::transaction(function () use ($user, $permissions, $actor): User {
            $before = $user->getDirectPermissions()->pluck('name')->values();
            $delegablePermissions = collect($this->roleManagementService->permissions($actor))
                ->pluck('value');
            $protectedPermissions = $before->diff($delegablePermissions);
            $permissions = collect($permissions)
                ->merge($protectedPermissions)
                ->unique()
                ->values()
                ->all();

            $user->syncPermissions($permissions);

            $after = collect($permissions)->values();

            Log::info('Permissões individuais actualizadas', [
                'actor_id' => Auth::guard('tenant')->id(),
                'target_id' => $user->getKey(),
                'instituicao_id' => $user->instituicao_id,
                'added' => $after->diff($before)->values()->all(),
                'removed' => $before->diff($after)->values()->all(),
            ]);

            return $user->refresh();
        });
    }
}
