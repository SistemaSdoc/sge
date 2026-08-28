<?php

namespace App\Services\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Prepara consultas e dados das páginas de grupos PAP.
 */
class GrupoPapViewService
{
    public function index(
        User $user,
        ?string $anoLectivoId
    ): LengthAwarePaginator {
        $anoLectivoNome = $anoLectivoId
            ? AnoLectivo::query()->whereKey($anoLectivoId)->value('nome')
            : null;
        $groups = $this->groupsForTenant($user, $anoLectivoId);
        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        if ($this->isTutorInstitution($user)) {
            CursoTuteladoShared::query()
                ->where('tenant_tutor_id', $currentTenantId)
                ->where('status', 'activo')
                ->get()
                ->each(function (CursoTuteladoShared $shared) use (&$groups, $anoLectivoId, $anoLectivoNome, $user): void {
                    $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                    if (! $tenant) {
                        return;
                    }

                    $remoteGroups = $tenant->run(
                        fn (): SupportCollection => $this->groupsForTenant($user, $anoLectivoId, (string) $shared->getKey(), $anoLectivoNome)
                    )->each(function (GrupoPap $grupoPap): void {
                        $grupoPap->setAttribute('cross_tenant', true);
                    });

                    $groups = $groups->merge($remoteGroups);
                });
        }

        $groups = $groups->sortByDesc('created_at')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;

        return new LengthAwarePaginator(
            $groups->forPage($page, $perPage)->values(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    /**
     * Obtém grupos PAP de um tenant, aplicando os filtros locais.
     */
    private function groupsForTenant(
        User $user,
        ?string $anoLectivoId,
        ?string $sharedId = null,
        ?string $anoLectivoNome = null
    ): SupportCollection {
        $instituicaoId = $sharedId === null ? $user->instituicaoFiltro() : null;

        return GrupoPap::query()
            ->with([
                'professor.user:id,nome',
                'turma.cursoClasseTurno.turno:id,nome',
                'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'elementos.aluno.inscricao.candidato:id,nome',
            ])
            ->when($instituicaoId, fn ($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($institutionQuery) => $institutionQuery->where('instituicao_id', $instituicaoId)
            ))
            ->when($sharedId === null && $anoLectivoId, fn ($query) => $query->whereHas(
                'turma',
                fn ($turmaQuery) => $turmaQuery->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->when($sharedId !== null && $anoLectivoNome, fn ($query) => $query->whereHas(
                'turma.anoLectivo',
                fn ($anoQuery) => $anoQuery->where('nome', $anoLectivoNome)
            ))
            ->when($user->hasRole('Aluno'), fn ($query) => $query->whereHas(
                'alunos',
                fn ($alunosQuery) => $alunosQuery->where('aluno_id', $user->aluno?->id)
            ))
            ->when(
                $user->hasRole('Professor') && ! $user->hasPermissionTo('grupopap.viewAny'),
                fn ($query) => $query->where(function ($professorQuery) use ($user): void {
                    $professorId = $user->professor?->id;
                    $professorQuery->whereHas('turma.professores', fn ($query) => $query->where('professores.id', $professorId))
                        ->orWhereHas('jurados', fn ($query) => $query->where('professor_id', $professorId))
                        ->orWhere('professor_tutor_id', $professorId);
                })
            )
            ->when($sharedId !== null, fn ($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                fn ($courseQuery) => $courseQuery
                    ->where('tipo_tutela', 'externa')
                    ->where('curso_tutelado_shared_id', $sharedId)
            ))
            ->latest()
            ->get();
    }

    /**
     * Lista os cursos tutelados da instituição do utilizador.
     *
     * @return SupportCollection<int, array{id: string, nome: string, instituicao_id: string}>
     */
    public function tutoredCourses(User $user): SupportCollection
    {
        $courses = CursoTutelado::query()
            ->whereHas(
                'instituicaoCurso',
                fn ($query) => $query->where('instituicao_id', $user->instituicao_id)
            )
            ->with([
                'instituicaoCurso.curso:id,nome',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (CursoTutelado $cursoTutelado): array => [
                'id' => (string) $cursoTutelado->getKey(),
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                'instituicao_id' => (string) $user->instituicao_id,
            ]);

        if (! $this->isTutorInstitution($user)) {
            return $courses;
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $currentTenantId)
            ->where('status', 'activo')
            ->get()
            ->each(function (CursoTuteladoShared $shared) use (&$courses): void {
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                if (! $tenant) {
                    return;
                }

                $remoteCourses = $tenant->run(function () use ($shared): SupportCollection {
                    return CursoTutelado::query()
                        ->whereKey($shared->curso_tutelado_tutelado_id)
                        ->with('instituicaoCurso.curso:id,nome')
                        ->get()
                        ->map(fn (CursoTutelado $cursoTutelado): array => [
                            'id' => (string) $cursoTutelado->getKey(),
                            'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? $shared->curso_nome,
                            'instituicao_id' => (string) $cursoTutelado->instituicaoCurso?->instituicao_id,
                        ]);
                });

                $courses = $courses->merge($remoteCourses);
            });

        return $courses->unique(fn (array $course): string => $course['instituicao_id'].'-'.$course['id'])->values();
    }

    /**
     * Lista as instituições disponíveis para filtrar os grupos PAP.
     *
     * A instituição actual é sempre incluída; a policy continua a controlar o acesso aos grupos.
     *
     * @return SupportCollection<int, array{id: string, nome: string}>
     */
    public function papInstitutions(User $user): SupportCollection
    {
        $institutions = Instituicao::query()
            ->whereKey($user->instituicao_id)
            ->get(['id', 'nome'])
            ->map(fn (Instituicao $instituicao): array => [
                'id' => (string) $instituicao->getKey(),
                'nome' => $instituicao->nome,
            ]);

        if (! $this->isTutorInstitution($user)) {
            return $institutions;
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        $remoteInstitutions = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $currentTenantId)
            ->where('status', 'activo')
            ->get()
            ->map(function (CursoTuteladoShared $shared): ?array {
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);
                $instituicao = $tenant ? $tenant->run(fn (): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id)) : null;

                return $instituicao ? [
                    'id' => (string) $instituicao->getKey(),
                    'nome' => $instituicao->nome,
                ] : null;
            })
            ->filter()
            ->values();

        return $institutions->merge($remoteInstitutions)->unique('id')->sortBy('nome')->values();
    }

    private function isTutorInstitution(User $user): bool
    {
        return $user->instituicao?->tipo === 'instituto';
    }

    /**
     * @return array{professores: Collection, alunos: Collection}
     */
    public function createOptions(
        CursoTutelado $cursoTutelado,
        Turma $turma
    ): array {
        return [
            'professores' => Professor::query()
                ->whereHas('cursosTutelados', fn ($query) => $query
                    ->where('curso_tutelado_id', $cursoTutelado->getKey())
                    ->where('tipo', 'principal'))
                ->with('user:id,nome')
                ->get(),
            'alunos' => $this->availableStudents($turma),
        ];
    }

    /**
     * Obtém as opções do formulário de edição, mantendo os alunos do grupo.
     *
     * @return array{professores: Collection, alunos: Collection}
     */
    public function editOptions(
        CursoTutelado $cursoTutelado,
        Turma $turma,
        GrupoPap $grupoPap
    ): array {
        return [
            'professores' => Professor::query()
                ->whereHas('cursosTutelados', fn ($query) => $query
                    ->where('curso_tutelado_id', $cursoTutelado->getKey())
                    ->where('tipo', 'principal'))
                ->with('user:id,nome')
                ->get(),
            'alunos' => $turma->alunos()
                ->where(function ($query) use ($grupoPap): void {
                    $query->whereDoesntHave('grupoPap')
                        ->orWhereHas('grupoPap', fn ($grupoQuery) => $grupoQuery->whereKey($grupoPap->getKey()));
                })
                ->with('inscricao.candidato:id,nome')
                ->get()
                ->map(fn (Aluno $aluno): array => [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ]),
        ];
    }

    /**
     * @return Collection<int, Aluno>
     */
    private function availableStudents(Turma $turma): Collection
    {
        $alunosEmGrupo = ElementoGrupoPap::query()->pluck('aluno_id');

        return Aluno::query()
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', fn ($query) => $query
                ->where('turmas.id', $turma->getKey())
                ->where('turma_aluno.activo', true))
            ->with('inscricao.candidato:id,nome')
            ->get()
            ->map(fn (Aluno $aluno): array => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ]);
    }

    public function prepareShow(GrupoPap $grupoPap): void
    {
        $grupoPap->load([
            'professor.user:id,nome,email',
            'historicoAprovacao.utilizador:id,nome,instituicao_id',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ]);
    }

    /**
     * @return array{banca: LengthAwarePaginator, elementos: LengthAwarePaginator}
     */
    public function paginatedDetails(GrupoPap $grupoPap): array
    {
        return [
            'banca' => $grupoPap->jurados()
                ->with('professor.user:id,nome,email')
                ->paginate(10, ['*'], 'page_banca'),
            'elementos' => $grupoPap->elementos()
                ->with('aluno.inscricao.candidato:id,nome,email', 'aluno:id,matricula,inscricao_id')
                ->paginate(10, ['*'], 'page_elementos'),
        ];
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function history(
        GrupoPap $grupoPap,
        ?string $instituicaoTutoraId,
        ?string $nomeCurso,
        ?string $siglaInstituto
    ): SupportCollection {
        return $grupoPap->historicoAprovacao->map(function ($item) use ($instituicaoTutoraId, $nomeCurso, $siglaInstituto): array {
            $ehTutora = $item->estado_novo !== 'pendente'
                && $item->utilizador?->instituicao_id === $instituicaoTutoraId;

            return [
                'id' => $item->id,
                'estado_anterior' => $item->estado_anterior,
                'estado_novo' => $item->estado_novo,
                'comentario' => $item->comentario,
                'tema' => $item->tema,
                'problema' => $item->problema,
                'objectivos' => $item->objectivos,
                'created_at' => $item->created_at?->toIso8601String(),
                'utilizador' => [
                    'nome' => $ehTutora
                        ? "Grupo disciplinar do curso de {$nomeCurso} do {$siglaInstituto}"
                        : ($item->utilizador?->nome ?? '—'),
                ],
            ];
        })->values();
    }

    public function academicYears(): Collection
    {
        return AnoLectivo::all();
    }
}
