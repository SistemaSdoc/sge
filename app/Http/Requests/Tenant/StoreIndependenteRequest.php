<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Turma;
use App\Rules\ProfessorTitularDoCurso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIndependenteRequest extends FormRequest
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
        $cursoTutelado = $this->input('curso_tutelado_id')
            ? CursoTutelado::find($this->input('curso_tutelado_id'))
            : null;

        return [
            'curso_tutelado_id' => ['required', 'exists:curso_tutelado,id'],
            'curso_classe_id' => ['nullable', 'exists:curso_classe,id'],
            'curso_classe_turno_id' => ['nullable', 'exists:curso_classe_turno,id'],
            'turma_id' => ['required', 'exists:turmas,id'],
            'professor_tutor_id' => [
                'required',
                'exists:professores,id',
                $cursoTutelado ? new ProfessorTitularDoCurso($cursoTutelado) : null,
            ],
            'nome_grupo' => 'required|string|max:255',
            'tema_grupo' => 'nullable|string|max:255',
            'problema' => 'nullable|string',
            'objectivos' => 'nullable|string',
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
            'curso_tutelado_id.required' => 'Selecione um curso tutelado.',
            'curso_tutelado_id.exists' => 'O curso selecionado não existe.',
            'turma_id.required' => 'Selecione uma turma.',
            'turma_id.exists' => 'A turma selecionada não existe.',
            'professor_tutor_id.required' => 'Selecione um professor tutor.',
            'professor_tutor_id.exists' => 'O professor selecionado não existe.',
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'alunos.required' => 'Seleciona pelo menos um aluno.',
            'alunos.min' => 'Seleciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos selecionados não existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $afterValidator) {
            $turmaId = $this->input('turma_id');

            if (! $turmaId) {
                return;
            }

            $turma = Turma::with('cursoClasseTurno.cursoClasse.classe')->find($turmaId);
            $classeNome = $turma?->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

            if (! str_contains(strtolower($classeNome), '13')) {
                $afterValidator->errors()->add('turma_id', 'Os grupos PAP só podem ser criados para turmas da 13ª classe.');
            }
        });
    }
}
