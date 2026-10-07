<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $actor */
        $actor = $this->user('tenant');
        $target = $this->route('user');

        return $actor instanceof User
            && $target instanceof User
            && $actor->can('managePermissions', $target);
    }

    public function rules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::in($this->allowedPermissions())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'permissions.array' => 'A lista de permissões enviada é inválida.',
            'permissions.*.string' => 'Uma das permissões seleccionadas é inválida.',
            'permissions.*.distinct' => 'A lista contém permissões duplicadas.',
            'permissions.*.in' => 'Uma ou mais permissões seleccionadas não podem ser atribuídas por si.',
        ];
    }

    /** @return array<int, string> */
    private function allowedPermissions(): array
    {
        /** @var User $user */
        $user = auth()->guard('tenant')->user();

        if (! $user?->isSubdirector()) {
            return collect(app(RoleManagementService::class)->permissions())
                ->pluck('value')
                ->all();
        }

        return collect(app(RoleManagementService::class)->permissions($user))
            ->pluck('value')
            ->all();
    }
}
