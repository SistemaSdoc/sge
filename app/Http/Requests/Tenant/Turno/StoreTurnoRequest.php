<?php

namespace App\Http\Requests\Tenant\Turno;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTurnoRequest extends FormRequest
{
    /**
     * Determina se o usuario pode criar um turno.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação para a criação de um turno.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Obtém as mensagens de validação personalizadas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do turno é obrigatório.',
            'nome.string' => 'O nome do turno deve ser uma string.',
            'nome.max' => 'O nome do turno deve ter no máximo 50 caracteres.',
        ];
    }
}
