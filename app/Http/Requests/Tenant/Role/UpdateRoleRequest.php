<?php

namespace App\Http\Requests\Tenant\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'not_in:SuperAdmin', Rule::unique('roles', 'name')->where('guard_name', 'tenant')->ignore($role),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')->where('guard_name', 'tenant')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da role é obrigatório.',
            'name.string' => 'O nome da role deve ser um texto válido.',
            'name.max' => 'O nome da role não pode ultrapassar 100 caracteres.',
            'name.not_in' => 'A role SuperAdmin não pode ser gerida neste módulo.',
            'name.unique' => 'Já existe outra role com este nome.',
            'permissions.array' => 'A lista de permissões deve ser válida.',
            'permissions.*.exists' => 'Uma das permissões selecionadas não existe.',
        ];
    }
}
