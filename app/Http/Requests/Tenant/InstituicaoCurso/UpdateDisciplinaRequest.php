<?php

namespace App\Http\Requests\Tenant\InstituicaoCurso;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisciplinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:255'],
            'carga_horaria' => ['sometimes', 'integer', 'min:1'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.string' => 'O nome da disciplina deve ser uma string.',
            'nome.max' => 'O nome da disciplina não pode exceder 255 caracteres.',
            'carga_horaria.integer' => 'A carga horária deve ser um número inteiro.',
            'carga_horaria.min' => 'A carga horária deve ser pelo menos 1 hora.',
            'descricao.string' => 'A descrição deve ser uma string.',
        ];
    }
}
