<?php

namespace App\Http\Requests\InstituicaoCurso;

use Illuminate\Foundation\Http\FormRequest;

class StoreTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'                   => ['required', 'string', 'max:255'],
            'curso_classe_turno_id'  => ['required', 'exists:curso_classe_turno,id'],
            'max_alunos'             => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'                  => 'O nome da turma é obrigatório.',
            'curso_classe_turno_id.required' => 'A classe e turno são obrigatórios.',
            'curso_classe_turno_id.exists'   => 'A classe e turno seleccionados são inválidos.',
        ];
    }
}
