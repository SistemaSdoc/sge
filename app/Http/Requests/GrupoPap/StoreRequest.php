<?php

namespace App\Http\Requests\GrupoPap;

use App\Rules\ProfessorTitularDoCurso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'professor_tutor_id' => [
                'required',
                'exists:professores,id',
                new ProfessorTitularDoCurso($this->route('cursoTutelado')),
            ],
            'nome_grupo' => 'required|string|max:255',
            'tema_grupo' => 'required|string|max:255',
            'alunos' => 'required|array|min:1',
            'alunos.*' => 'exists:alunos,id',
            'estudo_caso' => 'nullable|string',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'data_defesa' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'professor_tutor_id.required' => 'Selecione um professor tutor.',
            'professor_tutor_id.exists' => 'O professor selecionado não existe.',
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'tema_grupo.required' => 'O tema do grupo é obrigatório.',
            'alunos.required' => 'Seleciona pelo menos um aluno.',
            'alunos.min' => 'Seleciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos selecionados não existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $turma = $this->route('turma');
            $classe = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome;

            if ($classe !== '13ª') {
                $validator->errors()->add('turma', 'Os grupos PAP só podem ser criados para turmas da 13ª classe.');
            }
        });
    }
}
