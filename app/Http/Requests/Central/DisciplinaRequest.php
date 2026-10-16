<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisciplinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SuperAdmin') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('disciplinas', 'nome')
                    ->ignore($this->route('disciplina')?->getKey())
                    ->withoutTrashed(),
            ],
            'sigla' => ['nullable', 'string', 'max:50'],
            'componente' => ['nullable', Rule::in(['sociocultural', 'cientifica', 'tecnica'])],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da disciplina é obrigatório.',
            'nome.unique' => 'Já existe uma disciplina com este nome.',
            'carga_horaria.required' => 'A carga horária é obrigatória.',
            'carga_horaria.min' => 'A carga horária deve ser de pelo menos 1 hora.',
            'status.in' => 'O status seleccionado é inválido.',
        ];
    }
}
