<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TurmaRequest extends FormRequest
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
            'nome' => 'required|string|max:255',
            'max_alunos' => 'required|integer|min:1',
            'curso_classe_turno_id' => 'required|exists:curso_classe_turno,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da turma é obrigatório.',
            'max_alunos.required' => 'O número máximo de alunos é obrigatório.',
            'max_alunos.integer' => 'O número máximo de alunos deve ser um inteiro.',
            'max_alunos.min' => 'O número máximo de alunos deve ser pelo menos 1.',
            'curso_classe_turno_id.exists' => 'O curso, classe e turno selecionados são inválidos.',
        ];
    }
}
