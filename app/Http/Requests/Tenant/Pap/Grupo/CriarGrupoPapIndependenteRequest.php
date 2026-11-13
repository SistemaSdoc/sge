<?php

namespace App\Http\Requests\Tenant\Pap\Grupo;

use App\Models\Tenant\Turma;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida a criação de um grupo PAP a partir do formulário independente.
 */
class CriarGrupoPapIndependenteRequest extends FormRequest
{
    /**
     * Mantém a autorização no controller, conforme o padrão actual do módulo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Devolve as regras de validação do formulário independente.
     *
     * @return array<string, array<mixed>|string>
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
            'nome_grupo' => ['required', 'string', 'max:255'],
            'tema_grupo' => [
                'nullable',
                'string',
                'max:255',
                ...($cursoTuteladoId && $anoLectivoId && $cursoClasseTurnoId
                    ? [new TemaPapUnico(
                        (string) $cursoTuteladoId,
                        (string) $anoLectivoId,
                        (string) $cursoClasseTurnoId,
                    )]
                    : []),
            ],
            'problema' => ['nullable', 'string'],
            'objectivo_geral' => ['nullable', 'string'],
            'objectivos_especificos' => ['nullable', 'string'],
            'alunos' => ['required', 'array', 'min:1'],
            'alunos.*' => ['exists:alunos,id'],
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
            'nota_final' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'data_defesa' => ['nullable', 'date'],
        ];
    }

    /**
     * Devolve as mensagens específicas do formulário independente.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'curso_tutelado_id.required' => 'Seleccione um curso tutelado.',
            'curso_tutelado_id.exists' => 'O curso seleccionado não existe.',
            'turma_id.required' => 'Seleccione uma turma.',
            'turma_id.exists' => 'A turma seleccionada não existe.',
            'nome_grupo.required' => 'O nome do grupo é obrigatório.',
            'alunos.required' => 'Selecciona pelo menos um aluno.',
            'alunos.min' => 'Selecciona pelo menos um aluno.',
            'alunos.*.exists' => 'Um dos alunos seleccionados não existe.',
        ];
    }

    /**
     * Acrescenta validações que dependem da turma seleccionada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $afterValidator): void {
            if (! $this->input('turma_id')) {
                return;
            }

            $turma = $this->turma();
            $classeNome = $turma?->cursoClasseTurno?->cursoClasse?->classe?->nome ?? '';

            if (! str_contains(strtolower($classeNome), '13')) {
                $afterValidator->errors()->add(
                    'turma_id',
                    'Os grupos PAP só podem ser criados para turmas da 13ª classe.'
                );
            }

            $cursoTuteladoId = $turma?->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;

            if ($cursoTuteladoId && (string) $cursoTuteladoId !== (string) $this->input('curso_tutelado_id')) {
                $afterValidator->errors()->add(
                    'curso_tutelado_id',
                    'O curso seleccionado não corresponde à turma.'
                );
            }
        });
    }

    /**
     * Carrega a turma enviada no formulário com a hierarquia curricular necessária.
     */
    private function turma(): ?Turma
    {
        return Turma::query()
            ->with('cursoClasseTurno.cursoClasse.classe')
            ->find($this->input('turma_id'));
    }
}
