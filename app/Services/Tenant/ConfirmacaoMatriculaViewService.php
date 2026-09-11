<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\User;
use App\Services\Tenant\Core\RegraAcademicaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Prepara os dados da página de confirmação de matrículas.
 */
final class ConfirmacaoMatriculaViewService
{
    public function __construct(
        private readonly RegraAcademicaService $regraAcademicaService,
    ) {}

    public function listarAlunos(Turma $turma, ?string $instituicaoId = null): LengthAwarePaginator
    {
        $anoActual = $turma->anoLectivo ?: AnoLectivo::activo();

        if (! $anoActual) {
            return collect()->paginate(10);
        }

        $anoProximo = $this->proximoAno($anoActual);

        return TurmaAluno::query()
            ->whereHas('turma', function ($query) use ($anoActual, $turma, $instituicaoId): void {
                $query->where('ano_lectivo_id', $anoActual->id)
                    ->whereKey($turma->id);

                if ($instituicaoId) {
                    $query->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                        fn ($institutionQuery) => $institutionQuery->where('instituicao_id', $instituicaoId)
                    );
                }
            })
            ->whereHas('notas', fn ($query) => $query->where('periodo', 1)->whereNotNull('media_trimestral'), '>=', 1)
            ->whereHas('notas', fn ($query) => $query->where('periodo', 2)->whereNotNull('media_trimestral'), '>=', 1)
            ->whereHas('notas', fn ($query) => $query->where('periodo', 3)->whereNotNull('media_trimestral'), '>=', 1)
            ->where('activo', true)
            ->when($anoProximo, fn ($query) => $query->whereDoesntHave(
                'aluno.confirmacoesMatricula',
                fn ($confirmationQuery) => $confirmationQuery
                    ->where('status', 'confirmada')
                    ->where('ano_lectivo_proximo_id', $anoProximo->id)
            ))
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'aluno.user:id,nome',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.classes',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            ])
            ->orderBy('created_at')
            ->paginate(10)
            ->through(function (TurmaAluno $turmaAluno): array {
                /** @var User|null $user */
                $user = auth('tenant')->user();
                $turmaActual = $turmaAluno->turma;
                $classeActual = $turmaActual?->cursoClasseTurno?->cursoClasse?->classe;
                $status = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAluno)['situacao'];

                return [
                    'id' => $turmaAluno->aluno?->id,
                    'nome' => $turmaAluno->aluno?->inscricao?->candidato?->nome
                        ?? $turmaAluno->aluno?->user?->nome
                        ?? 'Desconhecido',
                    'curso' => $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                    'classe_actual' => $classeActual?->nome,
                    'classe_proximo_ano' => in_array($status, ['transita', 'transita_com_deficiencia', 'aprovado_recurso'], true)
                        ? $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->classes
                            ?->firstWhere('ordem', ($classeActual?->ordem ?? 0) + 1)?->nome
                        : $classeActual?->nome,
                    'turno' => $turmaActual?->cursoClasseTurno?->turno?->nome,
                    'turma' => $turmaActual?->nome,
                    'status' => $status,
                    'aguarda_resultado_recurso' => $status === 'recurso',
                    'can' => [
                        'confirmar_matricula' => ! in_array($status, ['incompleto', 'recurso'], true)
                            && $user?->can('confirmar', $turmaAluno),
                    ],
                ];
            });
    }

    /**
     * @return array{ano: array{id: string, nome: string}|null, anos: Collection, turmas: Collection}
     */
    public function opcoes(Turma $turma, Instituicao $instituicao): array
    {
        $anoProximo = $turma->anoLectivo ? $this->proximoAno($turma->anoLectivo) : null;

        $turmas = $anoProximo
            ? Turma::query()
                ->where('ano_lectivo_id', $anoProximo->id)
                ->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado',
                    fn ($query) => $query->whereKey($turma->cursoClasseTurno?->cursoClasse?->curso_tutelado_id)
                        ->whereHas('instituicaoCurso', fn ($institutionQuery) => $institutionQuery->where('instituicao_id', $instituicao->id))
                )
                ->with('cursoClasseTurno.turno')
                ->get()
                ->map(fn (Turma $item): array => [
                    'id' => $item->id,
                    'nome' => $item->nome,
                    'turno' => $item->cursoClasseTurno?->turno?->nome,
                    'max_alunos' => $item->max_alunos,
                ])
            : collect();

        return [
            'ano' => $anoProximo ? ['id' => $anoProximo->id, 'nome' => $anoProximo->nome] : null,
            'anos' => $anoProximo ? collect([['id' => $anoProximo->id, 'nome' => $anoProximo->nome, 'activo' => $anoProximo->activo]]) : collect(),
            'turmas' => $turmas,
        ];
    }

    private function proximoAno(AnoLectivo $anoActual): ?AnoLectivo
    {
        return AnoLectivo::query()
            ->where('id', '!=', $anoActual->id)
            ->where('data_inicio', '>', $anoActual->data_inicio)
            ->orderBy('data_inicio')
            ->first();
    }
}
