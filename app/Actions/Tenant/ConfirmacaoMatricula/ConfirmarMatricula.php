<?php

namespace App\Actions\Tenant\ConfirmacaoMatricula;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\ConfirmacaoMatricula as ConfirmacaoMatriculaModel;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Confirma a matrícula de um aluno no ano lectivo seguinte.
 */
final class ConfirmarMatricula
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {}

    public function handle(
        Instituicao $instituicao,
        Aluno $aluno,
        Turma $turmaNova,
        Turma $turmaAtual,
    ): ConfirmacaoMatriculaModel {
        return DB::transaction(function () use ($instituicao, $aluno, $turmaNova, $turmaAtual): ConfirmacaoMatriculaModel {
            /** @var TurmaAluno|null $turmaAlunoActual */
            $turmaAlunoActual = TurmaAluno::query()
                ->with([
                    'turma.anoLectivo',
                    'turma.cursoClasseTurno.cursoClasse.classe',
                    'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                    'turma.turmaDisciplinaProfessor',
                    'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
                ])
                ->where('aluno_id', $aluno->id)
                ->where('turma_id', $turmaAtual->id)
                ->lockForUpdate()
                ->first();

            if (! $turmaAlunoActual) {
                throw ValidationException::withMessages([
                    'aluno_id' => 'Aluno não está associado à turma actual.',
                ]);
            }

            $turmaActual = $turmaAlunoActual->turma;
            $anoActual = $turmaActual?->anoLectivo;

            if (! $anoActual) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'A turma actual não tem ano lectivo definido.',
                ]);
            }

            $anoProximo = AnoLectivo::query()
                ->where('id', '!=', $anoActual->id)
                ->where('data_inicio', '>', $anoActual->data_inicio)
                ->orderBy('data_inicio')
                ->first();

            if (! $anoProximo) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'Não existe próximo ano lectivo configurado.',
                ]);
            }

            $turmaDestino = Turma::query()
                ->with([
                    'anoLectivo',
                    'cursoClasseTurno.cursoClasse.classe',
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                ])
                ->whereKey($turmaNova->id)
                ->lockForUpdate()
                ->firstOrFail();

            $cursoTuteladoActualId = $turmaActual->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
            $cursoTuteladoDestinoId = $turmaDestino->cursoClasseTurno?->cursoClasse?->curso_tutelado_id;
            $instituicaoDestinoId = $turmaDestino->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao_id;

            if ($instituicaoDestinoId !== $instituicao->id || $cursoTuteladoActualId !== $cursoTuteladoDestinoId) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'A turma seleccionada não pertence ao curso e à instituição actuais.',
                ]);
            }

            if ((string) $turmaDestino->ano_lectivo_id !== (string) $anoProximo->id) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'A turma seleccionada não pertence ao próximo ano lectivo.',
                ]);
            }

            $situacao = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAlunoActual)['situacao'];

            if (in_array($situacao, ['incompleto', 'recurso'], true)) {
                throw ValidationException::withMessages([
                    'aluno_id' => 'A situação académica do aluno ainda não está fechada.',
                ]);
            }

            $ordemActual = $turmaActual->cursoClasseTurno?->cursoClasse?->classe?->ordem;
            $ordemDestino = $turmaDestino->cursoClasseTurno?->cursoClasse?->classe?->ordem;
            $ordemEsperada = in_array($situacao, ['transita', 'transita_com_deficiencia', 'aprovado_recurso'], true)
                && $ordemActual !== null
                ? $ordemActual + 1
                : $ordemActual;

            if ($ordemEsperada !== null && $ordemDestino !== $ordemEsperada) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'A turma seleccionada não corresponde à situação académica do aluno.',
                ]);
            }

            $jaConfirmou = ConfirmacaoMatriculaModel::query()
                ->where('aluno_id', $aluno->id)
                ->where('ano_lectivo_proximo_id', $anoProximo->id)
                ->where('status', 'confirmada')
                ->lockForUpdate()
                ->exists();

            if ($jaConfirmou) {
                throw ValidationException::withMessages([
                    'aluno_id' => 'Este aluno já confirmou matrícula para o próximo ano.',
                ]);
            }

            $jaEstaNaTurma = TurmaAluno::query()
                ->where('aluno_id', $aluno->id)
                ->where('turma_id', $turmaDestino->id)
                ->where('ano_lectivo_id', $anoProximo->id)
                ->where('activo', true)
                ->lockForUpdate()
                ->exists();

            if ($jaEstaNaTurma) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'Este aluno já está matriculado na turma seleccionada para o próximo ano.',
                ]);
            }

            $alunosActivos = TurmaAluno::query()
                ->where('turma_id', $turmaDestino->id)
                ->where('ano_lectivo_id', $anoProximo->id)
                ->where('activo', true)
                ->count();

            if ($turmaDestino->max_alunos !== null && $alunosActivos >= $turmaDestino->max_alunos) {
                throw ValidationException::withMessages([
                    'turma_nova_id' => 'A turma seleccionada já atingiu a capacidade máxima.',
                ]);
            }

            $confirmacao = ConfirmacaoMatriculaModel::create([
                'aluno_id' => $aluno->id,
                'ano_lectivo_atual_id' => $anoActual->id,
                'ano_lectivo_proximo_id' => $anoProximo->id,
                'turma_atual_id' => $turmaActual->id,
                'turma_nova_id' => $turmaDestino->id,
                'status' => 'confirmada',
                'data_confirmacao' => now(),
                'confirmado_por' => Auth::guard('tenant')->id(),
            ]);

            TurmaAluno::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turmaDestino->id,
                'ano_lectivo_id' => $anoProximo->id,
                'activo' => true,
                'situacao' => 'activo',
            ]);

            return $confirmacao;
        });
    }
}
