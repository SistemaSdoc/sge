<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\CursoClasseTurno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConfirmacaoMatriculaService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {}

    /**
     * Lista os alunos que precisam de confirmar matrícula no ano lectivo
     * seguinte.
     *
     * Devem aparecer tanto os alunos que transitam para a classe seguinte
     * como os que ficam na mesma classe (repetem), porque ambos entram em
     * lista de confirmação no novo ano lectivo.
     *
     * @return LengthAwarePaginator<int, array{
     *     id: ?string,
     *     nome: string,
     *     curso: ?string,
     *     classe_actual: ?string,
     *     classe_proximo_ano: ?string,
     *     turno: ?string,
     *     turma: ?string,
     *     status: ?string,
     * }>
     */
    public function listarAlunosPorConfirmarMatricula(): LengthAwarePaginator
    {
        return TurmaAluno::query()
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'aluno.user:id,nome',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.classes',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'turma.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            ])
            ->where('activo', true)
            ->whereIn('situacao', ['activo', 'retido'])
            ->orderBy('created_at')
            ->paginate(10)
            ->through(function (TurmaAluno $turmaAluno): array {
                $aluno = $turmaAluno->aluno;
                $turmaActual = $turmaAluno->turma;
                $classeActual = $turmaActual?->cursoClasseTurno?->cursoClasse?->classe?->nome;
                $ordemClasseActual = $turmaActual?->cursoClasseTurno?->cursoClasse?->classe?->ordem;
                $status = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAluno)['situacao'];

                $classeProximoAno = match (true) {
                    in_array($status, ['transita', 'transita_com_deficiencia'], true) => $this->resolverProximaClasse($turmaActual, $ordemClasseActual),
                    default => $classeActual,
                };

                return [
                    'id' => $aluno?->id,
                    'nome' => $aluno?->inscricao?->candidato?->nome
                        ?? $aluno?->user?->nome
                        ?? 'Desconhecido',
                    'curso' => $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                    'classe_actual' => $classeActual,
                    'classe_proximo_ano' => $classeProximoAno,
                    'turno' => $turmaActual?->cursoClasseTurno?->turno?->nome,
                    'turma' => $turmaActual?->nome,
                    'status' => $status,
                ];
            });
    }

    private function resolverProximaClasse(?Turma $turmaActual, ?int $ordemClasseActual): ?string
    {
        $cursoTutelado = $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado;

        if ($cursoTutelado === null || $ordemClasseActual === null) {
            return null;
        }

        return $cursoTutelado
            ->classes()
            ->where('ordem', $ordemClasseActual + 1)
            ->value('nome');
    }

    /**
     * Confirma a matrícula de um aluno para o próximo ano lectivo.
     *
     * Se o aluno transitou, a matrícula é criada na turma da classe seguinte.
     * Se o aluno não transitou, a matrícula é criada na mesma classe do
     * próximo ano lectivo.
     */
    public function confirmarMatricula(Aluno $aluno): Aluno
    {
        $turmaAlunoActual = TurmaAluno::query()
            ->with([
                'turma.anoLectivo',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.classes',
                'turma.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            ])
            ->where('aluno_id', $aluno->id)
            ->where('activo', true)
            ->whereIn('situacao', ['activo', 'retido'])
            ->orderByDesc('created_at')
            ->firstOrFail();

        $status = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAlunoActual)['situacao'];
        $turmaDestino = $this->resolverTurmaDestino($turmaAlunoActual, $status);

        if ($turmaDestino === null) {
            throw new \RuntimeException('Não foi possível encontrar a turma de destino para confirmar a matrícula.');
        }

        DB::transaction(function () use ($turmaAlunoActual, $turmaDestino, $status): void {
            TurmaAluno::create([
                'turma_id' => $turmaDestino->id,
                'aluno_id' => $turmaAlunoActual->aluno_id,
                'activo' => true,
                'situacao' => 'activo',
                'resultado' => $status,
            ]);

            $turmaAlunoActual->update([
                'activo' => false,
                'situacao' => in_array($status, ['transita', 'transita_com_deficiencia'], true)
                    ? 'transitado'
                    : 'retido',
                'resultado' => $status,
            ]);
        });

        return $aluno;
    }

    private function resolverTurmaDestino(TurmaAluno $turmaAlunoActual, ?string $status): ?Turma
    {
        $turmaActual = $turmaAlunoActual->turma;
        $anoLectivoActual = $turmaActual?->anoLectivo;

        if ($anoLectivoActual === null || $anoLectivoActual->data_inicio === null) {
            return null;
        }

        $anoLectivoDestino = $this->resolverAnoLectivoDestino($anoLectivoActual);
        $cursoClasseTurnoActual = $turmaActual?->cursoClasseTurno;
        $cursoClasseActual = $cursoClasseTurnoActual?->cursoClasse;
        $classeActual = $cursoClasseActual?->classe;

        if (in_array($status, ['transita', 'transita_com_deficiencia'], true)) {
            if ($classeActual === null || $classeActual->ordem === null) {
                return null;
            }

            $cursoClasseTurnoDestino = CursoClasseTurno::query()
                ->where('turno_id', $cursoClasseTurnoActual?->turno_id)
                ->whereHas('cursoClasse', function ($query) use ($cursoClasseActual, $classeActual): void {
                    $query->where('curso_tutelado_id', $cursoClasseActual?->curso_tutelado_id)
                        ->whereHas('classe', function ($subQuery) use ($classeActual): void {
                            $subQuery->where('ordem', $classeActual->ordem + 1);
                        });
                })
                ->first();

            if ($cursoClasseTurnoDestino === null) {
                return null;
            }

            return Turma::query()
                ->where('ano_lectivo_id', $anoLectivoDestino->id)
                ->where('curso_classe_turno_id', $cursoClasseTurnoDestino->id)
                ->first();
        }

        return Turma::query()
            ->where('ano_lectivo_id', $anoLectivoDestino->id)
            ->where('curso_classe_turno_id', $cursoClasseTurnoActual?->id)
            ->first();
    }

    private function resolverAnoLectivoDestino(AnoLectivo $anoLectivoActual): AnoLectivo
    {
        return AnoLectivo::query()
            ->whereDate('data_inicio', '>', $anoLectivoActual->data_inicio)
            ->orderBy('data_inicio')
            ->first() ?? $anoLectivoActual;
    }
}

