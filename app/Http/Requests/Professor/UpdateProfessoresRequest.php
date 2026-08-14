<?php

namespace App\Http\Requests\Professor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessoresRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $professor = $this->route('professor');
        $userId = $professor?->user_id;

        return [
            'nome' => 'string|max:255',
            'bi' => ['string', 'max:20', Rule::unique('users', 'bi')->ignore($userId)],
            'email' => ['email', Rule::unique('users', 'email')->ignore($userId)],
            'telefone' => 'max:20',
            'especialidade' => 'nullable|string|max:255',
            'nivel_academico' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.string' => 'O nome deve ser uma string.',
            'bi.string' => 'O BI deve ser uma string.',
            'email.email' => 'O email deve ser um email válido.',
            'telefone.max' => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}
