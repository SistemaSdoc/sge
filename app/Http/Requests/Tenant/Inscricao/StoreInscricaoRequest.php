<?php

namespace App\Http\Requests\Tenant\Inscricao;

use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Rules\CentralAnoLectivoExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ─── Obrigatórios na inscrição ───
            'nome' => 'required|string|max:255',
            'bi' => [
                'required',
                'string',
                'max:20',
                'unique:candidatos,bi',
                'unique:users,bi',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:candidatos,email',
                'unique:users,email',
            ],

            'curso_classe_turno_id' => [
                'required',
                'exists:curso_classe_turno,id',
            ],

            'turma_id' => [
                'nullable',
                'exists:turmas,id',
            ],

            'nota_teste' => [
                'required',
                'numeric',
                'min:0',
                'max:20',
            ],

            'ano_lectivo_id' => [
                'nullable',
                'uuid',
                new CentralAnoLectivoExists,
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['curso_classe_turno_id', 'turma_id', 'ano_lectivo_id'])) {
                return;
            }

            /** @var User|null $user */
            $user = $this->user('tenant');

            $cursoClasseTurno = CursoClasseTurno::query()
                ->with('cursoClasse.cursoTutelado.instituicaoCurso.curso')
                ->find($this->input('curso_classe_turno_id'));

            $turma = Turma::query()->find($this->input('turma_id'));
            $cursoTutelado = $cursoClasseTurno?->cursoClasse?->cursoTutelado;

            if ($user?->instituicao_id
                && (string) $cursoTutelado?->instituicaoCurso?->instituicao_id !== (string) $user->instituicao_id) {
                $validator->errors()->add(
                    'curso_classe_turno_id',
                    'O curso seleccionado não pertence à sua instituição.',
                );

                return;
            }

            if (! $cursoClasseTurno || ! $turma
                || (string) $turma->curso_classe_turno_id !== (string) $cursoClasseTurno->getKey()) {
                $validator->errors()->add('turma_id', 'A turma seleccionada não pertence ao turno escolhido.');
            }

            if ($turma && filled($this->input('ano_lectivo_id'))
                && (string) $turma->ano_lectivo_id !== (string) $this->input('ano_lectivo_id')) {
                $validator->errors()->add('turma_id', 'A turma seleccionada não pertence ao ano lectivo escolhido.');
            }

            if ($user?->hasRole('Secretario do Curso')) {
                $isAssignedSecretary = $cursoTutelado?->secretarios()
                    ->whereKey($user->getKey())
                    ->exists() ?? false;

                if (! $isAssignedSecretary
                    || (string) $cursoTutelado?->instituicaoCurso?->instituicao_id !== (string) $user->instituicao_id) {
                    $validator->errors()->add(
                        'curso_classe_turno_id',
                        'Só pode criar matrículas em cursos aos quais está associado.',
                    );

                    return;
                }
            }

            $curso = $cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->curso;

            if ($curso?->trashed()) {
                $validator->errors()->add(
                    'curso_classe_turno_id',
                    'O curso selecionado está arquivado e não pode receber novas matrículas.'
                );
            }
        }];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'bi.required' => 'O número de BI é obrigatório.',
            'bi.unique' => 'Já existe um registo com este número de BI.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email introduzido não é válido.',
            'email.unique' => 'Já existe um registo com este email.',
            'curso_classe_turno_id.required' => 'O curso/turno é obrigatório.',
            'curso_classe_turno_id.exists' => 'O curso/turno seleccionado não existe.',
            'turma_id.exists' => 'A turma seleccionada não existe.',
            'nota_teste.numeric' => 'A nota deve ser numérica.',
            'nota_teste.min' => 'A nota não pode ser inferior a 0.',
            'nota_teste.max' => 'A nota não pode ser superior a 20.',
            'nota_teste.required' => 'A nota é obrigatória.',
        ];
    }
}
