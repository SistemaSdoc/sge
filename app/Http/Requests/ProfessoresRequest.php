<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfessoresRequest extends FormRequest
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
            'nome' => 'required|string|max:255',
            'bi' => 'required|string|max:20|unique:users,bi,' . $userId,
            'email' => 'required|email|unique:users,email,' . $userId,
            'telefone' => 'required|max:20',
            'especialidade' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'bi.required' => 'O BI é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'telefone.required' => 'O telefone é obrigatório.',
        ];
    }
}
