<?php

namespace App\Http\Requests\Central\Curso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCursoRequest extends FormRequest
{
    /**
     * Determina se o utilizador pode actualizar um curso.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SuperAdmin') ?? false;
    }

    /**
     * Obtém as regras de validação para a actualização de um curso.
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
                Rule::unique('cursos', 'nome')
                    ->ignore($this->route('curso')?->getKey())
                    ->withoutTrashed(),
            ],
            'descricao' => ['nullable', 'string'],
            'duracao_anos' => ['required', 'integer', 'min:1', 'max:10'],
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
            'nome.required' => 'O nome do curso é obrigatório.',
            'nome.unique' => 'Já existe um curso com este nome.',
            'duracao_anos.required' => 'A duração do curso é obrigatória.',
            'duracao_anos.min' => 'A duração deve ser de pelo menos 1 ano.',
            'status.in' => 'O status seleccionado é inválido.',
        ];
    }
}
