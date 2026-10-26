<?php

namespace App\Http\Requests\Tenant\AccessManagement;

use App\Models\Tenant\User;
use App\Services\Tenant\RoleManagementService;
use App\Services\Tenant\Users\UserManagementService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleAndPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User|null $actor */
        $actor = $this->user('tenant');
        $target = $this->route('user');

        return $actor instanceof User
            && $target instanceof User
            && $actor->can('managePermissions', $target);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => [
                'string',
                Rule::in($this->allowedRoles()),
            ],
            'directPermissions' => ['array'],
            'directPermissions.*' => [
                'string',
                'distinct',
                Rule::in($this->allowedPermissions()),
            ],
        ];
    }

    /** @return array<int, string> */
    private function allowedRoles(): array
    {
        /** @var User|null $actor */
        $actor = $this->user('tenant');
        $target = $this->route('user');

        if (! $actor instanceof User || ! $target instanceof User) {
            return [];
        }

        return collect(app(UserManagementService::class)->roles($actor, $target))
            ->pluck('name')
            ->all();
    }

    /** @return array<int, string> */
    private function allowedPermissions(): array
    {
        /** @var User|null $actor */
        $actor = $this->user('tenant');

        return $actor instanceof User
            ? collect(app(RoleManagementService::class)->permissions($actor))->pluck('value')->all()
            : [];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Selecciona um role para atribuir.',
            'role.exists' => 'Role inválido.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('role') === 'SuperAdmin') {
                $validator->errors()->add('role', 'Role inválido.');
            }
        });
    }
}
