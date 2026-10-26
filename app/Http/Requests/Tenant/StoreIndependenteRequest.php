<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\Turma;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
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
        $turma = $this->turma();
        $cursoTuteladoId = $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
        $anoLectivoId = $turma?->ano_lectivo_id;
        $cursoClasseTurnoId = $turma?->curso_classe_turno_id;

        return [
            'curso_tutelado_id' => ['required', 'exists:curso_tutelado,id'],
            'curso_classe_id' => ['nullable', 'exists:curso_classe,id'],
            'curso_classe_turno_id' => ['nullable', 'exists:curso_classe_turno,id'],
            'turma_id' => ['required', 'exists:turmas,id'],
            'nome_grupo' => 'required|string|max:255',
            'tema_grupo' => [
                'nullable',
                'string',
                'max:255',
                ...($cursoTuteladoId && $anoLectivoId && $cursoClasseTurnoId
                    ? [new TemaPapUnico((string) $cursoTuteladoId, (string) $anoLectivoId, (string) $cursoClasseTurnoId)]
                    : []),
            ],
            'problema' => 'nullable|string',
            'objectivos' => 'nullable|string',
            'alunos' => 'required|array|min:1',
            'alunos.*' => 'exists:alunos,id',
            'estudo_caso' => [
                'nullable',
                'string',
                ...($cursoTuteladoId && $anoLectivoId && $cursoClasseTurnoId
                    ? [new EstudoCasoPapUnico(
                        (string) $cursoTuteladoId,
                        (string) $anoLectivoId,
                        (string) $cursoClasseTurnoId,
                        $this->input('tema_grupo'),
                    )]
                    : []),
            ],
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

            $turma = $this->turma();
            $classeNome = $turma?->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

            if (! str_contains(strtolower($classeNome), '13')) {
                $afterValidator->errors()->add('turma_id', 'Os grupos PAP só podem ser criados para turmas da 13ª classe.');
            }

            $cursoTuteladoId = $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;

            if ($cursoTuteladoId && (string) $cursoTuteladoId !== (string) $this->input('curso_tutelado_id')) {
                $afterValidator->errors()->add('curso_tutelado_id', 'O curso seleccionado não corresponde à turma.');
            }
        });
    }

    private function turma(): ?Turma
    {
        return Turma::with('cursoClasseTurno.cursoClasse.classe')
            ->find($this->input('turma_id'));
    }
}
