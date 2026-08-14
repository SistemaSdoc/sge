<?php

namespace App\Http\Requests\InstituicaoCurso;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disciplina_id' => ['required', 'exists:disciplinas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'disciplina_id.required' => 'A disciplina é obrigatória.',
            'disciplina_id.exists' => 'A disciplina selecionada é inválida.',
        ];
    }
}
