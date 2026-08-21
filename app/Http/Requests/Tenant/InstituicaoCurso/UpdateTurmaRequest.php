<?php

namespace App\Http\Requests\Tenant\InstituicaoCurso;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:255'],

            'curso_classe_turno_id' => [
                'sometimes',
                'exists:curso_classe_turno,id',
            ],

            'max_alunos' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.string' => 'O nome da turma deve ser uma string.',
            'nome.max' => 'O nome da turma não pode exceder 255 caracteres.',
            'curso_classe_turno_id.exists' => 'O curso, classe e turno selecionados são inválidos.',
            'max_alunos.integer' => 'O número máximo de alunos deve ser um inteiro.',
            'max_alunos.min' => 'O número máximo de alunos deve ser pelo menos 1.',
        ];
    }
}
