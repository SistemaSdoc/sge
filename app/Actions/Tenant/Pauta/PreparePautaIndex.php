<?php

namespace App\Actions\Tenant\Pauta;

use App\Models\Central\AnoLectivo;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Prepara as instituições, cursos e turmas disponíveis para consultar pautas.
 */
class PreparePautaIndex
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
    ) {}

    /**
     * Obtém os dados da listagem de turmas com os filtros seleccionados.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $filtros = $request->query();
        $instituicaoId = (string) $user->instituicao_id;
        $instituicaoIdFiltro = (string) ($filtros['instituicao_id'] ?? $instituicaoId);
        $cursoTuteladoIdFiltro = $filtros['curso_tutelado_id'] ?? null;
        $anoLectivoId = filled($filtros['ano_lectivo_id'] ?? null)
            ? (string) $filtros['ano_lectivo_id']
            : (string) $this->anoLectivoResolverService->obterAnoLectivoDefault();
        $isProfessor = $user->hasRole('Professor');
        $professorId = $user->professor?->id;

        // NOVO: cursos onde o user é coordenador
        $idsCursosCoordenados = $isProfessor
            ? ($user->professor?->cursosTutelados()->wherePivot('coordenador', true)->get()->modelKeys() ?? [])
            : [];

        $instituicoes = collect();
        $instituicaoActual = Instituicao::query()->find($instituicaoId);

        if ($instituicaoActual) {
            $instituicoes->push([
                'id' => (string) $instituicaoActual->getKey(),
                'nome' => $instituicaoActual->nome,
            ]);
        }

        $cursos = collect();
        $turmas = collect();

        // ALTERADO: só vínculos que o user pode ver (permissão ou coordenador)
        $vinculosActivos = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('status', 'activo')
            ->get()
            ->filter(fn (CursoTuteladoShared $vinculo): bool => $user->can('pautas.viewAny')
                || $this->ehCoordenadorDoCursoPartilhado($user, $vinculo));

        if ($instituicaoIdFiltro === $instituicaoId) {
            $cursosLocais = CursoTutelado::query()
                ->where(function ($query) use ($instituicaoId): void {
                    $query->where('instituicao_tutora_id', $instituicaoId)
                        ->orWhereHas(
                            'instituicaoCurso',
                            fn ($query) => $query->where('instituicao_id', $instituicaoId),
                        );
                })
                ->when($isProfessor, fn ($query) => $query->whereHas(
                    'professores',
                    fn ($query) => $query->where('professor_id', $professorId),
                ))
                ->with([
                    'instituicaoCurso.curso:id,nome',
                    'instituicaoCurso.instituicao:id,nome',
                ])
                ->get();

            foreach ($cursosLocais as $cursoTutelado) {
                $cursos->push([
                    'id' => (string) $cursoTutelado->getKey(),
                    'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                    'remote' => false,
                ]);
            }

            $idsCursos = $cursosLocais->modelKeys();
            $turmasLocais = Turma::query()
                ->whereHas(
                    'cursoClasseTurno.cursoClasse',
                    fn ($query) => $query->whereIn('curso_tutelado_id', $idsCursos),
                )
                ->where('ano_lectivo_id', $anoLectivoId)
                ->when(filled($cursoTuteladoIdFiltro), fn ($query) => $query->whereHas(
                    'cursoClasseTurno.cursoClasse',
                    fn ($query) => $query->where('curso_tutelado_id', $cursoTuteladoIdFiltro),
                ))
                // ALTERADO: professor vê as suas turmas OU todas as dos cursos que coordena
                ->when($isProfessor, fn ($query) => $query->where(function ($query) use ($professorId, $idsCursosCoordenados): void {
                    $query->whereHas('professores', fn ($q) => $q->where('professor_id', $professorId))
                        ->orWhereHas(
                            'cursoClasseTurno.cursoClasse',
                            fn ($q) => $q->whereIn('curso_tutelado_id', $idsCursosCoordenados),
                        );
                }))
                ->with($this->turmaRelations())
                ->orderBy('nome')
                ->get();

            $turmas = $turmas->merge(
                $turmasLocais->map(fn (Turma $turma): array => $this->mapTurma($turma, $user)),
            );
        }

        // ALTERADO: $isProfessor e $professorId saíram dos use()
        $vinculosActivos->groupBy('tenant_tutelado_id')
            ->each(function (Collection $vinculosTenant, string $tenantId) use (&$cursos, &$instituicoes, &$turmas, $instituicaoIdFiltro, $cursoTuteladoIdFiltro, $anoLectivoId, $user): void {
                $tenantTutelado = Tenant::query()->find($tenantId);

                if (! $tenantTutelado) {
                    return;
                }

                $dadosTenant = $tenantTutelado->run(function () use ($tenantTutelado, $vinculosTenant, $instituicaoIdFiltro, $cursoTuteladoIdFiltro, $anoLectivoId): array {
                    $instituicao = Instituicao::query()->find($tenantTutelado->instituicao_id);
                    $resultado = [
                        'instituicao' => $instituicao ? [
                            'id' => (string) $instituicao->getKey(),
                            'nome' => $instituicao->nome,
                        ] : null,
                        'cursos' => collect(),
                        'turmas' => collect(),
                    ];

                    if ((string) $tenantTutelado->instituicao_id !== $instituicaoIdFiltro) {
                        return $resultado;
                    }

                    $cursosRemotos = CursoTutelado::query()
                        ->whereIn('id', $vinculosTenant->pluck('curso_tutelado_tutelado_id'))
                        ->with('instituicaoCurso.curso:id,nome')
                        ->get();

                    $resultado['cursos'] = $cursosRemotos->map(
                        fn (CursoTutelado $cursoTutelado): array => [
                            'id' => (string) $cursoTutelado->getKey(),
                            'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                            'remote' => true,
                        ],
                    );

                    $idsCursos = filled($cursoTuteladoIdFiltro)
                        ? $cursosRemotos->where('id', $cursoTuteladoIdFiltro)->modelKeys()
                        : $cursosRemotos->modelKeys();

                    if ($idsCursos === []) {
                        return $resultado;
                    }

                    // ALTERADO: removido o when($isProfessor, ...)
                    $resultado['turmas'] = Turma::query()
                        ->whereHas(
                            'cursoClasseTurno.cursoClasse',
                            fn ($query) => $query->whereIn('curso_tutelado_id', $idsCursos),
                        )
                        ->where('ano_lectivo_id', $anoLectivoId)
                        ->with($this->turmaRelations())
                        ->orderBy('nome')
                        ->get();

                    return $resultado;
                });

                if ($dadosTenant['instituicao']) {
                    $instituicoes->push($dadosTenant['instituicao']);
                }

                $cursos = $cursos->merge($dadosTenant['cursos']);
                $turmas = $turmas->merge(
                    $dadosTenant['turmas']->map(fn (Turma $turma): array => $this->mapTurma($turma, $user, true)),
                );
            });

        $instituicoes = $instituicoes->unique('id')->sortBy('nome')->values();
        abort_unless($instituicoes->contains('id', $instituicaoIdFiltro), 404);

        $cursos = $cursos->unique('id')->sortBy('nome')->values();
        $cursoTuteladoIdFiltro = filled($cursoTuteladoIdFiltro)
            && $cursos->contains('id', (string) $cursoTuteladoIdFiltro)
            ? (string) $cursoTuteladoIdFiltro
            : null;
        $turmas = $turmas
            ->when($cursoTuteladoIdFiltro, fn ($items) => $items->where('curso_tutelado_id', $cursoTuteladoIdFiltro))
            ->when(filled($filtros['search'] ?? null), function (Collection $items) use ($filtros): Collection {
                $term = mb_strtolower(trim((string) $filtros['search']));

                return $items->filter(fn (array $turma): bool => collect([
                    $turma['nome'] ?? '',
                    $turma['classe'] ?? '',
                    $turma['turno'] ?? '',
                    $turma['curso'] ?? '',
                ])->contains(
                    fn ($value): bool => str_contains(mb_strtolower((string) $value), $term),
                ));
            })
            ->sortBy(fn (array $turma): string => $turma['nome'].'-'.$turma['curso'].'-'.$turma['id'])
            ->values();
        $porPagina = min(100, max(1, $request->integer('per_page', 10)));
        $pagina = max(1, $request->integer('page', 1));
        $turmasPaginadas = new LengthAwarePaginator(
            $turmas->forPage($pagina, $porPagina)->values(),
            $turmas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url()],
        );
        $turmasPaginadas->appends($request->except('page'));

        return [
            'instituicao' => $instituicoes->firstWhere('id', $instituicaoIdFiltro),
            'instituicoes' => $instituicoes,
            'cursos' => $cursos,
            'turmas' => $turmasPaginadas,
            'anosLectivos' => AnoLectivo::all(),
            'filtros' => [
                'instituicao_id' => $instituicaoIdFiltro,
                'curso_tutelado_id' => $cursoTuteladoIdFiltro,
                'ano_lectivo_id' => $anoLectivoId,
                'search' => $filtros['search'] ?? '',
            ],
        ];
    }

    /**
     * Define as relações necessárias para apresentar as turmas.
     *
     * @return array<int, string>
     */
    private function turmaRelations(): array
    {
        return [
            'cursoClasseTurno.cursoClasse.classe:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'anoLectivo:id,nome',
        ];
    }

    /**
     * Define  que o coodenador do curso do instituto deve ver dados do colégio no curso em que está associado.
     *
     * @return array<int, string>
     */
    private function ehCoordenadorDoCursoPartilhado(User $user, CursoTuteladoShared $shared): bool
    {
        $professorId = $user->professor?->id;

        if ($professorId === null || $shared->curso_id === null) {
            return false;
        }

        return $user->professor->cursosTutelados()
            ->whereHas('instituicaoCurso', fn ($q) => $q->where('curso_id', $shared->curso_id))
            ->wherePivot('coordenador', true)
            ->exists();
    }

    /**
     * Formata uma turma e indica se o utilizador pode consultar a pauta.
     *
     * @return array<string, mixed>
     */
    private function mapTurma(Turma $turma, User $user, bool $remoteTutor = false): array
    {
        $cursoTutelado = $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado;

        return [
            'id' => (string) $turma->getKey(),
            'nome' => $turma->nome,
            'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'turno' => $turma->cursoClasseTurno?->turno?->nome,
            'curso' => $cursoTutelado?->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
            'curso_tutelado_id' => (string) $cursoTutelado?->getKey(),
            'can' => [
                'view_pauta' => $remoteTutor || $user->can('pauta.view', $turma),
            ],
        ];
    }
}
