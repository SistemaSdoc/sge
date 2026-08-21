<?php

namespace App\Http\Requests\Tenant\AccessManagement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleAndPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('gerir permissoes');
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
            'roles.*' => ['string', 'exists:roles,name'],
            'directPermissions' => ['array'],
            'directPermissions.*' => ['string', 'exists:permissions,name'],
        ];
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
