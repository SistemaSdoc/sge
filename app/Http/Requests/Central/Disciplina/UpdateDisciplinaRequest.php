<?php

namespace App\Http\Requests\Central\Disciplina;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisciplinaRequest extends FormRequest
{
    /**
     * Determina se o usuario pode actualizar uma disciplina.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SuperAdmin') ?? false;
    }

    /**
     * Obtém as regras de validação para a actualização de uma disciplina.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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

    /**
     * Obtém as mensagens de validação personalizadas.
     *
     * @return array<string, string>
     */
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
