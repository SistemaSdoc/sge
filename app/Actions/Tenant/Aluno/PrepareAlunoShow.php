<?php

namespace App\Actions\Tenant\Aluno;

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\PreencherHistoricoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Prepara o perfil, o histórico e as opções académicas do aluno. */
class PrepareAlunoShow
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly PreencherHistoricoService $preencherHistoricoService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Aluno $aluno, Request $request, User $user): array
    {
        $anoLectivoId = filled($request->query('ano_lectivo_id'))
            ? $request->query('ano_lectivo_id')
            : $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $aluno->load([
            'inscricao.candidato:id,nome,bi,email,telefone,genero,nacionalidade,naturalidade,morada,filiacao,data_nascimento',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn ($query) => $query->wherePivot('activo', true)
                ->where('turmas.ano_lectivo_id', $anoLectivoId)
                ->with([
                    'cursoClasseTurno.cursoClasse.classe:id,nome',
                    'anoLectivo:id,nome',
                ]),
        ])->where('inscricao.status', '!=', 'cancelado');

        $turmaNoAno = $aluno->turmas()
            ->wherePivot('activo', true)
            ->where('turmas.ano_lectivo_id', $anoLectivoId)
            ->with(['cursoClasseTurno.cursoClasse.classe', 'anoLectivo'])
            ->first();

        if (! $turmaNoAno) {
            $turmaNoAno = $aluno->turmas()
                ->wherePivot('activo', true)
                ->wherePivot('is_historico', false)
                ->with(['cursoClasseTurno.cursoClasse.classe', 'anoLectivo'])
                ->latest('turmas.created_at')
                ->first();
        }

        $canManageHistorico = $user->can('manageHistorico', $aluno);
        $pendentes = $canManageHistorico
            ? $this->preencherHistoricoService->obterClassesFaltando($aluno)
            : [];

        $alunoComInscricao = Aluno::find($aluno->id);
        $inscricao = $alunoComInscricao->inscricao?->loadMissing([
            'cursoClasseTurno.cursoClasse.classe',
        ]);

        $cursoClasseActual = $inscricao?->cursoClasseTurno?->cursoClasse;
        $cursoTuteladoId = $cursoClasseActual?->curso_tutelado_id;

        if ($canManageHistorico && $cursoTuteladoId) {
            $pendingClassIds = collect($pendentes)->pluck('curso_classe_id');
            $historicoClassIds = CursoClasse::query()
                ->where('curso_tutelado_id', $cursoTuteladoId)
                ->whereIn('id', $pendingClassIds)
                ->pluck('id')
                ->map(fn ($id): string => (string) $id)
                ->all();

            $pendentes = collect($pendentes)
                ->filter(fn (array $pending): bool => in_array((string) $pending['curso_classe_id'], $historicoClassIds, true))
                ->values()
                ->all();
        }

        $anosLectivos = AnoLectivo::where('activo', false)
            ->orderBy('data_fim', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($anoLectivo) => [
                'id' => $anoLectivo->id,
                'nome' => $anoLectivo->nome,
            ])
            ->toArray();

        $turnos = Inertia::optional(
            fn () => $request->filled('ano_lectivo_id') && $request->filled('curso_classe_id')
                ? CursoClasseTurno::query()
                    ->where('curso_classe_id', $request->query('curso_classe_id'))
                    ->when(
                        $canManageHistorico,
                        fn ($query) => $query->whereHas(
                            'cursoClasse',
                            fn ($query) => $query->where('curso_tutelado_id', $cursoTuteladoId),
                        ),
                    )
                    ->with('turno:id,nome')
                    ->get()
                    ->map(fn ($cursoClasseTurno) => [
                        'id' => $cursoClasseTurno->id,
                        'turno_nome' => $cursoClasseTurno->turno?->nome,
                    ])
                : []
        );

        $turmasPorTurno = Inertia::optional(
            fn () => $request->filled('ano_lectivo_id') && $request->filled('curso_classe_turno_id')
                ? Turma::query()
                    ->where('ano_lectivo_id', $request->query('ano_lectivo_id'))
                    ->where('curso_classe_turno_id', $request->query('curso_classe_turno_id'))
                    ->when(
                        $canManageHistorico,
                        fn ($query) => $query->whereHas(
                            'cursoClasseTurno.cursoClasse',
                            fn ($query) => $query->where('curso_tutelado_id', $cursoTuteladoId),
                        ),
                    )
                    ->get()
                    ->map(fn ($turma) => [
                        'id' => $turma->id,
                        'nome' => $turma->nome,
                    ])
                : []
        );

        return [
            'aluno' => [
                'id' => $aluno->id,
                'matricula' => $aluno->matricula,
                'inscricao_id' => $aluno->inscricao_id,
                'numero_processo' => $aluno->numero_processo,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'bi' => $aluno->inscricao?->candidato?->bi,
                'email' => $aluno->inscricao?->candidato?->email,
                'telefone' => $aluno->inscricao?->candidato?->telefone,
                'nacionalidade' => $aluno->inscricao?->candidato?->nacionalidade,
                'naturalidade' => $aluno->inscricao?->candidato?->naturalidade,
                'morada' => $aluno->inscricao?->candidato?->morada,
                'filiacao' => $aluno->inscricao?->candidato?->filiacao,
                'data_nascimento' => $aluno->inscricao?->candidato?->data_nascimento,
                'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'instituicao' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
                'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
                'turma' => [
                    'id' => $turmaNoAno?->id,
                    'nome' => $turmaNoAno?->nome,
                    'classe' => $turmaNoAno?->cursoClasseTurno?->cursoClasse?->classe?->nome,
                    'ano_lectivo' => $turmaNoAno?->anoLectivo?->nome,
                ],
                'can' => [
                    'view' => $user->can('view', $aluno),
                    'update' => $user->can('update', $aluno),
                    'delete' => $user->can('delete', $aluno),
                    'cancelar_matricula' => $aluno->inscricao !== null
                        && $user->can('cancelar', $aluno->inscricao),
                    'manageHistorico' => $canManageHistorico,
                ],
                'remocao' => $user->isSuperAdmin() ? $aluno->podeSerRemovido() : null,
            ],
            'historicoPendente' => $pendentes,
            'classesFaltando' => $pendentes,
            'anosLectivos' => $anosLectivos,
            'turnos' => $turnos,
            'turmasPorTurno' => $turmasPorTurno,
        ];
    }
}
