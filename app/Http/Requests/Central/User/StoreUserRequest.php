<?php

namespace App\Http\Requests\Central\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determina se o usuario pode criar outro usuario.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação para a criação de um usuario.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')],
            'password' => ['required', 'string', 'min:6'],
            'telefone' => ['nullable', 'string', 'max:255', Rule::unique('users')],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
