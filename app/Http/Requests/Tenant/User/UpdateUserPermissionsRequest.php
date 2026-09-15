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
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($this->allowedPermissions())],
        ];
    }

    /** @return array<int, string> */
    private function allowedPermissions(): array
    {
        /** @var User $actor */
        $actor = auth()->guard('tenant')->user();

        if (! $actor?->isSubdirector()) {
            return app(RoleManagementService::class)->permissions();
        }

        return app(RoleManagementService::class)->permissions($actor);
    }
}
