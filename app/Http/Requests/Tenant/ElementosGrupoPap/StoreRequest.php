<?php

namespace App\Http\Requests\Tenant\ElementosGrupoPap;

use App\Rules\AlunoNaoPertencenteAoGrupo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'alunos' => 'required|array|min:1',
            'alunos.*' => [
                'exists:alunos,id',
                new AlunoNaoPertencenteAoGrupo($this->route('grupoPap')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'alunos.required' => 'Seleciona pelo menos um aluno.',
            'alunos.min' => 'Seleciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos selecionados não existe.',
        ];
    }
}
