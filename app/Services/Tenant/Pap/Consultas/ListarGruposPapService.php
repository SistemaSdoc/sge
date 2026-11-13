<?php

namespace App\Services\Tenant\Pap\Consultas;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class ListarGruposPapService
{
    /**
     * Define o serviço que valida os acessos entre tenants.
     */
    public function __construct(
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Lista os grupos PAP visíveis ao utilizador no tenant local e nos colégios ligados.
     */
    public function listar(
        User $user,
        ?string $anoLectivoId,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoIdFiltro = null,
        ?string $search = null,
        bool $incluirDetalhesExportacao = false,
    ): Collection {
        $isCourseSecretary = $user->hasRole('Secretario do Curso');
        $isInstituteSecretary = $isCourseSecretary && $this->isTutorInstitution($user);
        $instituicaoIdPadrao = $instituicaoIdFiltro ?? $user->instituicao_id;

        $groups = $this->gruposDoTenant(
            $user,
            $anoLectivoId,
            null,
            $instituicaoIdPadrao,
            $cursoIdFiltro,
            $search,
            $incluirDetalhesExportacao,
        );

        if ($this->isTutorInstitution($user)) {
            $this->crossTenantAccessService->vinculosVisiveisNoPap($user)
                ->each(function (CursoTuteladoShared $shared) use (
                    &$groups,
                    $anoLectivoId,
                    $user,
                    $instituicaoIdFiltro,
                    $cursoIdFiltro,
                    $search,
                    $instituicaoIdPadrao,
                    $isInstituteSecretary,
                    $incluirDetalhesExportacao,
                ): void {
                    $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                    if (! $tenant) {
                        return;
                    }

                    if (
                        $instituicaoIdFiltro === null
                        && ! $isInstituteSecretary
                        && $tenant->instituicao_id !== $instituicaoIdPadrao
                    ) {
                        return;
                    }

                    if ($instituicaoIdFiltro && $tenant->instituicao_id !== $instituicaoIdFiltro) {
                        return;
                    }

                    $remoteGroups = $tenant->run(
                        fn (): Collection => $this->gruposDoTenant(
                            $user,
                            $anoLectivoId,
                            (string) $shared->getKey(),
                            $instituicaoIdFiltro ?? $instituicaoIdPadrao,
                            $cursoIdFiltro,
                            $search,
                            $incluirDetalhesExportacao,
                        )
                    );

                    $remoteGroups->each(function (GrupoPap $grupoPap) use ($isInstituteSecretary): void {
                        $grupoPap->setAttribute('cross_tenant', true);
                        $grupoPap->setAttribute('secretaria_course_access', $isInstituteSecretary);
                    });

                    $groups = $groups->merge($remoteGroups);
                });
        }

        return $groups->sortByDesc('created_at')->values();
    }

    /**
     * Lista os grupos PAP de uma turma específica.
     */
    public function gruposDaTurma(
        User $user,
        Turma $turma,
        string $anoLectivoId,
        string $instituicaoId,
        string $cursoId,
    ): Collection {
        return $this->gruposDoTenant(
            $user,
            $anoLectivoId,
            null,
            $instituicaoId,
            $cursoId,
            null,
            true,
            (string) $turma->getKey(),
        );
    }

    /**
     * Obtém os grupos visíveis no tenant activo com os filtros indicados.
     */
    private function gruposDoTenant(
        User $user,
        ?string $anoLectivoId,
        ?string $sharedId = null,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoIdFiltro = null,
        ?string $search = null,
        bool $incluirDetalhesExportacao = false,
        ?string $turmaId = null,
    ): Collection {
        $instituicaoId = $sharedId === null
            ? ($instituicaoIdFiltro ?? $user->instituicaoFiltro())
            : null;

        $query = GrupoPap::query()
            ->search($search)
            ->when($turmaId, fn ($query) => $query->where('turma_id', $turmaId))
            ->when($instituicaoId, fn ($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($q) => $q->where('instituicao_id', $instituicaoId)
            ))
            ->when(
                $sharedId === null && $user->hasRole('Secretario do Curso'),
                fn ($query) => $query->whereHas(
                    'turma.cursoClasseTurno.cursoClasse',
                    fn ($query) => $query->whereIn(
                        'curso_tutelado_id',
                        $user->cursosSecretariados()->select('curso_tutelado.id'),
                    ),
                ),
            )
            ->when($anoLectivoId, fn ($query) => $query->whereHas(
                'turma',
                fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId)
            ))
            ->when($user->hasRole('Aluno'), fn ($query) => $query->whereHas(
                'alunos',
                fn ($q) => $q->where('aluno_id', $user->aluno?->id)
            ))
            ->when(
                $sharedId === null
                    && $user->hasRole('Professor')
                    && ! $user->hasAnyRole(['Coordenador do Grupo Disciplinar', 'Membro do Grupo Disciplinar'])
                    && ! $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                fn ($query) => $query->where(function ($q) use ($user): void {
                    $professorId = $user->professor?->id;
                    $q->where('professor_tutor_id', $professorId)
                        ->orWhereHas(
                            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.professores',
                            fn ($p) => $p->where('professor_id', $professorId)
                                ->where('coordenador', true)
                        );
                })
            )
            ->when(
                $sharedId === null
                    && $user->hasRole('Professor')
                    && ! $user->hasAnyRole(['Coordenador do Grupo Disciplinar', 'Membro do Grupo Disciplinar'])
                    && $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                fn ($query) => $query->whereHas(
                    'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                    fn ($q) => $q->whereHas(
                        'professores',
                        fn ($p) => $p->where('professor_id', $user->professor?->id)
                            ->where('coordenador', true)
                    )
                )
            )
            ->when($sharedId !== null, fn ($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
                fn ($q) => $q->where('tipo_tutela', 'externa')->where('curso_tutelado_shared_id', $sharedId)
            ))
            ->when($cursoIdFiltro, fn ($query) => $query->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn ($q) => $q->where('curso_id', $cursoIdFiltro)
            ));

        $relations = [
            'professor.user:id,nome',
            'turma.cursoClasseTurno.turno:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ];

        if ($incluirDetalhesExportacao) {
            $relations = [
                'professor.user:id,nome,email,telefone',
                'turma.cursoClasseTurno.turno:id,nome',
                'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'elementos.aluno.inscricao.candidato:id,nome,bi,email,numero_estudante,telefone',
                'elementos.aluno.user:id,nome,bi,email,telefone',
            ];
        }

        return $query->with($relations)->latest()->get();
    }

    /**
     * Lista os cursos tutelados acessíveis ao utilizador.
     *
     * @return SupportCollection<int, array{id: string, nome: string, instituicao_id: string, curso_id: ?string}>
     */
    public function cursosTutelados(User $user, ?string $instituicaoIdFiltro = null): SupportCollection
    {
        $instituicaoIdLocal = (string) $user->instituicao_id;
        $courses = collect();

        if (! $instituicaoIdFiltro || $instituicaoIdFiltro === $instituicaoIdLocal) {
            /** @var EloquentCollection<int, CursoTutelado> $cursosTuteladosLocais */
            $cursosTuteladosLocais = CursoTutelado::query()
                ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $instituicaoIdLocal))
                ->when(
                    $user->hasRole('Secretario do Curso'),
                    fn ($query) => $query->whereHas('secretarios', fn ($secretarios) => $secretarios->whereKey($user->getKey())),
                )
                ->when(
                    $user->hasRole('Professor') && ! $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                    fn ($query) => $query->whereHas(
                        'professores',
                        fn ($professores) => $professores->where('professor_id', $user->professor?->id)
                    )
                )
                ->when(
                    $user->hasRole('Professor') && $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                    fn ($query) => $query->whereHas(
                        'professores',
                        fn ($professores) => $professores->where('professor_id', $user->professor?->id)
                            ->where('coordenador', true)
                    )
                )
                ->with(['instituicaoCurso.curso:id,nome'])
                ->orderBy('id')
                ->get();

            $courses = $cursosTuteladosLocais->map(fn (CursoTutelado $cursoTutelado): array => [
                'id' => (string) $cursoTutelado->getKey(),
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                'instituicao_id' => $instituicaoIdLocal,
                'curso_id' => $cursoTutelado->instituicaoCurso?->curso_id
                    ? (string) $cursoTutelado->instituicaoCurso->curso_id
                    : null,
            ]);
        }

        if (! $this->isTutorInstitution($user)) {
            return $courses
                ->unique(fn (array $course): string => "{$course['instituicao_id']}-{$course['id']}")
                ->values();
        }

        $this->crossTenantAccessService->vinculosVisiveisNoPap($user)
            ->each(function (CursoTuteladoShared $shared) use (&$courses, $instituicaoIdFiltro): void {
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                if (! $tenant) {
                    return;
                }

                if ($instituicaoIdFiltro !== null && (string) $tenant->instituicao_id !== $instituicaoIdFiltro) {
                    return;
                }

                $remoteCourses = $tenant->run(function () use ($shared, $tenant): SupportCollection {
                    /** @var EloquentCollection<int, CursoTutelado> $cursosTuteladosRemotos */
                    $cursosTuteladosRemotos = CursoTutelado::query()
                        ->whereKey($shared->curso_tutelado_tutelado_id)
                        ->with('instituicaoCurso.curso:id,nome')
                        ->get();

                    return $cursosTuteladosRemotos->map(fn (CursoTutelado $cursoTutelado): array => [
                        'id' => (string) $cursoTutelado->getKey(),
                        'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? $shared->curso_nome,
                        'instituicao_id' => (string) $tenant->instituicao_id,
                        'curso_id' => $cursoTutelado->instituicaoCurso?->curso_id
                            ? (string) $cursoTutelado->instituicaoCurso->curso_id
                            : ($shared->curso_id ? (string) $shared->curso_id : null),
                    ]);
                });

                $courses = $courses->merge($remoteCourses->all());
            });

        return $courses
            ->unique(fn (array $course): string => "{$course['instituicao_id']}-{$course['id']}")
            ->values();
    }

    /**
     * Prepara as opções de filtro de curso a partir dos cursos tutelados.
     *
     * @param  SupportCollection<int, array{id: string, nome: string, instituicao_id: string, curso_id: ?string}>  $courses
     * @return SupportCollection<int, array{id: string, nome: string}>
     */
    public function opcoesFiltroCurso(SupportCollection $courses): SupportCollection
    {
        return $courses
            ->filter(fn (array $course): bool => filled($course['curso_id']))
            ->unique(fn (array $course): string => $course['curso_id'])
            ->map(fn (array $course): array => [
                'id' => $course['curso_id'],
                'nome' => $course['nome'],
            ])
            ->sortBy('nome')
            ->values();
    }

    /**
     * Lista as instituições disponíveis para filtrar os grupos PAP.
     *
     * @return SupportCollection<int, array{id: string, nome: string}>
     */
    public function instituicoesPAP(User $user): SupportCollection
    {
        /** @var Collection<int, Instituicao> $instituicoesEncontradas */
        $instituicoesEncontradas = Instituicao::query()
            ->whereKey($user->instituicao_id)
            ->get(['id', 'nome']);

        $institutions = $instituicoesEncontradas->map(function ($instituicao): array {
            /** @var Instituicao $instituicao */
            return [
                'id' => (string) $instituicao->getKey(),
                'nome' => $instituicao->nome,
            ];
        });

        if (! $this->isTutorInstitution($user)) {
            return $institutions;
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();
        /** @var Collection<int, CursoTuteladoShared> $sharedLinks */
        $sharedLinks = $user->hasRole('Secretario do Curso')
            ? $this->crossTenantAccessService->vinculosSecretariados($user)
            : CursoTuteladoShared::query()
                ->where('tenant_tutor_id', $currentTenantId)
                ->where('status', 'activo')
                ->get();

        $remoteInstitutions = $sharedLinks
            ->map(function ($shared): ?array {
                /** @var CursoTuteladoShared $shared */
                $tenant = Tenant::query()->find($shared->tenant_tutelado_id);
                $instituicao = $tenant
                    ? $tenant->run(fn (): ?Instituicao => Instituicao::query()->find($tenant->instituicao_id))
                    : null;

                return $instituicao ? [
                    'id' => (string) $instituicao->getKey(),
                    'nome' => $instituicao->nome,
                ] : null;
            })
            ->filter()
            ->values();

        return $institutions->merge($remoteInstitutions)->unique('id')->sortBy('nome')->values();
    }

    /**
     * Indica se o utilizador pertence a uma instituição tutora.
     */
    private function isTutorInstitution(User $user): bool
    {
        return $user->instituicao?->tipo === 'instituto';
    }
}
