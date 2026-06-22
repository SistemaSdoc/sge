<?php

namespace App\Http\Requests\Curso;

use App\Models\Curso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CursoUpdateRequest extends FormRequest
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
        return [
            'nome' => 'required|string',
            'duracao_anos' => 'required|integer',
            'descricao' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe um curso com este nome.',
            'duracao_anos.required' => 'A duração em anos é obrigatória.',
            'duracao_anos.integer' => 'A duração em anos deve ser um número inteiro.',
        ];
    }
}
