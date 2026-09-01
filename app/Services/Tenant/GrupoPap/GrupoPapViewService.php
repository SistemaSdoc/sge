<?php

namespace App\Services\Tenant\GrupoPap;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use Illuminate\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Prepara consultas e dados das páginas de grupos PAP.
 */
class GrupoPapViewService
{
    public function index(
        User $user,
        ?string $anoLectivoId,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoTuteladoIdFiltro = null,
    ): LengthAwarePaginator {
        $instituicaoIdPadrao = $instituicaoIdFiltro ?? $user->instituicao_id;
        $anoLectivoNome = $anoLectivoId
            ? AnoLectivo::query()->whereKey($anoLectivoId)->value('nome')
            : null;

        $groups = $this->groupsForTenant($user, $anoLectivoId, null, null, $instituicaoIdPadrao, $cursoTuteladoIdFiltro);
        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        if ($this->isTutorInstitution($user)) {
            CursoTuteladoShared::query()
                ->where('tenant_tutor_id', $currentTenantId)
                ->where('status', 'activo')
                ->get()
                ->each(function (CursoTuteladoShared $shared) use (&$groups, $anoLectivoId, $anoLectivoNome, $user, $instituicaoIdFiltro, $cursoTuteladoIdFiltro, $instituicaoIdPadrao): void {
                    $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                    if (!$tenant) {
                        return;
                    }

                    if ($instituicaoIdFiltro === null && $tenant->instituicao_id !== $instituicaoIdPadrao) {
                        return;
                    }

                    if ($instituicaoIdFiltro && $tenant->instituicao_id !== $instituicaoIdFiltro) {
                        return;
                    }

                    $remoteGroups = $tenant->run(
                        fn(): SupportCollection => $this->groupsForTenant($user, $anoLectivoId, (string) $shared->getKey(), $anoLectivoNome, $instituicaoIdFiltro ?? $instituicaoIdPadrao, $cursoTuteladoIdFiltro)
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

    private function groupsForTenant(
        User $user,
        ?string $anoLectivoId,
        ?string $sharedId = null,
        ?string $anoLectivoNome = null,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoTuteladoIdFiltro = null,
    ): SupportCollection {
        // Para tenant local: usa o filtro explícito se vier, senão usa o da instituição do user
        $instituicaoId = $sharedId === null
            ? ($instituicaoIdFiltro ?? $user->instituicaoFiltro())
            : null;

        return GrupoPap::query()
            ->with([
                'professor.user:id,nome',
                'turma.cursoClasseTurno.turno:id,nome',
                'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'elementos.aluno.inscricao.candidato:id,nome',
            ])
            ->when($instituicaoId, fn($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn($q) => $q->where('instituicao_id', $instituicaoId)
            ))
            ->when($sharedId === null && $anoLectivoId, fn($query) => $query->whereHas(
                'turma',
                fn($q) => $q->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->when($sharedId !== null && $anoLectivoNome, fn($query) => $query->whereHas(
                'turma.anoLectivo',
                fn($q) => $q->where('nome', $anoLectivoNome)
            ))
            ->when($user->hasRole('Aluno'), fn($query) => $query->whereHas(
                'alunos',
                fn($q) => $q->where('aluno_id', $user->aluno?->id)
            ))
            ->when(
                $user->hasRole('Professor') && !$user->hasPermissionTo('grupopap.viewAny'),
                fn($query) => $query->where(function ($q) use ($user): void {
                    $professorId = $user->professor?->id;
                    $q->whereHas('turma.professores', fn($q) => $q->where('professores.id', $professorId))
                        ->orWhereHas('jurados', fn($q) => $q->where('professor_id', $professorId))
                        ->orWhere('professor_tutor_id', $professorId);
                })
            )
            ->when($sharedId !== null, fn($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                fn($q) => $q->where('tipo_tutela', 'externa')->where('curso_tutelado_shared_id', $sharedId)
            ))
            ->when($cursoTuteladoIdFiltro, fn($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse',
                fn($q) => $q->where('curso_tutelado_id', $cursoTuteladoIdFiltro)
            ))
            ->latest()
            ->get();
    }

    /**
     * Lista os cursos tutelados da instituição do utilizador.
     *
     * @return SupportCollection<int, array{id: string, nome: string, instituicao_id: string}>
     */
    public function tutoredCourses(User $user, ?string $instituicaoIdFiltro = null): SupportCollection
    {
        $instituicaoIdLocal = (string) $user->instituicao_id;
        $instituicaoAtiva = $instituicaoIdFiltro ?? $instituicaoIdLocal;

        $courses = collect();

        if (!$instituicaoIdFiltro || $instituicaoIdFiltro === $instituicaoIdLocal) {
            $courses = CursoTutelado::query()
                ->whereHas(
                    'instituicaoCurso',
                    fn($query) => $query->where('instituicao_id', $instituicaoIdLocal)
                )
                ->with(['instituicaoCurso.curso:id,nome'])
                ->orderBy('id')
                ->get()
                ->toBase()
                ->map(fn(CursoTutelado $ct): array => [
                    'id' => (string) $ct->getKey(),
                    'nome' => $ct->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                    'instituicao_id' => $instituicaoIdLocal,
                ]);
        }

        if (!$this->isTutorInstitution($user)) {
            return $courses;
        }

        if ($instituicaoIdFiltro === null) {
            return $courses
                ->unique(fn(array $c): string => $c['instituicao_id'] . '-' . $c['id'])
                ->values();
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $currentTenantId)
            ->where('status', 'activo')
            ->get()
            ->each(function (CursoTuteladoShared $shared) use (&$courses, $instituicaoIdFiltro): void {
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                if (!$tenant) {
                    return;
                }

                if ((string) $tenant->instituicao_id !== $instituicaoIdFiltro) {
                    return;
                }

                $remoteCourses = $tenant->run(function () use ($shared, $tenant): SupportCollection {
                    return CursoTutelado::query()
                        ->whereKey($shared->curso_tutelado_tutelado_id)
                        ->with('instituicaoCurso.curso:id,nome')
                        ->get()
                        ->map(fn(CursoTutelado $ct): array => [
                            'id' => (string) $ct->getKey(),
                            'nome' => $ct->instituicaoCurso?->curso?->nome ?? $shared->curso_nome,
                            'instituicao_id' => (string) $tenant->instituicao_id,
                        ]);
                });

                $courses = $courses->merge($remoteCourses->all());
            });

        return $courses
            ->unique(fn(array $c): string => $c['instituicao_id'] . '-' . $c['id'])
            ->values();
    }

    public function classesByCurso(int $cursoTuteladoId)
    {
        return CursoClasse::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn($q) => $q->where('nome', '13ª'))
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get()
            ->map(fn(CursoClasse $cc) => [
                'id' => $cc->id,
                'nome' => $cc->classe?->nome ?? $cc->nome,
            ]);
    }

    public function turnosByClasse(int $cursoClasseId)
    {
        return CursoClasseTurno::query()
            ->where('curso_classe_id', $cursoClasseId)
            ->with('turno:id,nome')
            ->get()
            ->map(fn($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno->nome,
            ]);
    }

    public function turmasByTurno(int $cursoClasseTurnoId)
    {
        return Turma::query()
            ->where('curso_classe_turno_id', $cursoClasseTurnoId)
            ->orderBy('nome')
            ->get(['id', 'nome']);
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
            ->map(fn(Instituicao $instituicao): array => [
                'id' => (string) $instituicao->getKey(),
                'nome' => $instituicao->nome,
            ]);

        if (!$this->isTutorInstitution($user)) {
            return $institutions;
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        $remoteInstitutions = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $currentTenantId)
            ->where('status', 'activo')
            ->get()
            ->map(function (CursoTuteladoShared $shared): ?array {
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);
                $instituicao = $tenant ? $tenant->run(fn(): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id)) : null;

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
                ->whereHas('cursosTutelados', fn($query) => $query
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
                ->whereHas('cursosTutelados', fn($query) => $query
                    ->where('curso_tutelado_id', $cursoTutelado->getKey())
                    ->where('tipo', 'principal'))
                ->with('user:id,nome')
                ->get(),
            'alunos' => $turma->alunos()
                ->where(function ($query) use ($grupoPap): void {
                    $query->whereDoesntHave('grupoPap')
                        ->orWhereHas('grupoPap', fn($grupoQuery) => $grupoQuery->whereKey($grupoPap->getKey()));
                })
                ->with('inscricao.candidato:id,nome')
                ->get()
                ->toBase()
                ->map(fn(Aluno $aluno): array => [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ]),
        ];
    }

    /**
     * @return SupportCollection<int, array{id: string, nome: string}>
     */
    private function availableStudents(Turma $turma): SupportCollection
    {
        $alunosEmGrupo = ElementoGrupoPap::query()->pluck('aluno_id');

        return Aluno::query()
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', fn($query) => $query
                ->where('turmas.id', $turma->getKey())
                ->where('turma_aluno.activo', true))
            ->with('inscricao.candidato:id,nome')
            ->get()
            ->toBase()
            ->map(fn(Aluno $aluno): array => [
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
