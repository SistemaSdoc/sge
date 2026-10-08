<?php

namespace App\Services\Tenant\GrupoPap;

use App\Helpers\PapHelper;
use App\Models\Central\AnoLectivo;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Prepara consultas e dados das páginas de grupos PAP.
 */
class GrupoPapViewService
{
    public function __construct(private readonly CrossTenantAccessService $crossTenantAccessService) {}

    public function index(
        User $user,
        ?string $anoLectivoId,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoIdFiltro = null,
        ?string $search = null,
    ): Collection {
        $isCourseSecretary = $user->hasRole('Secretario do Curso');
        $isInstituteSecretary = $isCourseSecretary && $this->isTutorInstitution($user);
        $instituicaoIdPadrao = $instituicaoIdFiltro ?? $user->instituicao_id;

        $localGroups = $this->groupsForTenant(
            $user,
            $anoLectivoId,
            null,
            $instituicaoIdPadrao,
            $cursoIdFiltro,
            $search,
        );
        $groups = $localGroups;

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
                ): void {
                    $tenant = Tenant::query()->find($shared->tenant_tutelado_id);

                    if (! $tenant) {
                        return;
                    }

                    if ($instituicaoIdFiltro === null
                        && ! $isInstituteSecretary
                        && $tenant->instituicao_id !== $instituicaoIdPadrao) {
                        return;
                    }

                    if ($instituicaoIdFiltro && $tenant->instituicao_id !== $instituicaoIdFiltro) {
                        return;
                    }

                    $remoteGroups = $tenant->run(
                        fn (): Collection => $this->groupsForTenant(
                            $user,
                            $anoLectivoId,
                            (string) $shared->getKey(),
                            $instituicaoIdFiltro ?? $instituicaoIdPadrao,
                            $cursoIdFiltro,
                            $search,
                        )
                    );
                    $remoteGroups->each(function (GrupoPap $grupoPap) use ($isInstituteSecretary): void {
                        $grupoPap->setAttribute('cross_tenant', true);
                        $grupoPap->setAttribute('secretaria_course_access', $isInstituteSecretary);
                    });

                    $groups = $groups->merge($remoteGroups);
                });
        }

        $groups = $groups->sortByDesc('created_at')->values();

        return $groups;
    }

    private function groupsForTenant(
        User $user,
        ?string $anoLectivoId,
        ?string $sharedId = null,
        ?string $instituicaoIdFiltro = null,
        ?string $cursoIdFiltro = null,
        ?string $search = null,
    ): Collection {
        // Para tenant local: usa o filtro explícito se vier, senão usa o da instituição do user
        $instituicaoId = $sharedId === null
            ? ($instituicaoIdFiltro ?? $user->instituicaoFiltro())
            : null;

        $query = GrupoPap::query()
            ->search($search)
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
            // Filtro por ano lectivo via FK directa em turmas — funciona tanto no tenant local
            // como no tenant remoto, sem precisar aceder à tabela ano_lectivos (que está no central)
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

        return $query
            ->with([
                'professor.user:id,nome',
                'turma.cursoClasseTurno.turno:id,nome',
                'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'elementos.aluno.inscricao.candidato:id,nome',
            ])
            ->latest()
            ->get();
    }

    /**
     * Lista os cursos tutelados da instituição do utilizador.
     *
     * @return SupportCollection<int, array{id: string, nome: string, instituicao_id: string, curso_id: ?string}>
     */
    public function tutoredCourses(User $user, ?string $instituicaoIdFiltro = null): SupportCollection
    {
        $instituicaoIdLocal = (string) $user->instituicao_id;

        $courses = collect();

        if (! $instituicaoIdFiltro || $instituicaoIdFiltro === $instituicaoIdLocal) {
            $courses = CursoTutelado::query()
                ->whereHas(
                    'instituicaoCurso',
                    fn ($query) => $query->where('instituicao_id', $instituicaoIdLocal)
                )
                ->when(
                    $user->hasRole('Secretario do Curso'),
                    fn ($query) => $query->whereHas(
                        'secretarios',
                        fn ($query) => $query->whereKey($user->getKey()),
                    ),
                )
                ->when(
                    $user->hasRole('Professor') && ! $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                    fn ($query) => $query->whereHas(
                        'professores',
                        fn ($q) => $q->where('professor_id', $user->professor?->id)
                    )
                )
                ->when(
                    $user->hasRole('Professor') && $user->hasPermissionTo('grupopap.selecionarInstituicao'),
                    fn ($query) => $query->whereHas(
                        'professores',
                        fn ($q) => $q->where('professor_id', $user->professor?->id)
                            ->where('coordenador', true)
                    )
                )
                ->with(['instituicaoCurso.curso:id,nome'])
                ->orderBy('id')
                ->get()
                ->toBase()
                ->map(fn (CursoTutelado $ct): array => [
                    'id' => (string) $ct->getKey(),
                    'nome' => $ct->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                    'instituicao_id' => $instituicaoIdLocal,
                    'curso_id' => $ct->instituicaoCurso?->curso_id
                        ? (string) $ct->instituicaoCurso->curso_id
                        : null,
                ]);
        }

        if (! $this->isTutorInstitution($user)) {
            return $courses
                ->unique(fn (array $c): string => $c['instituicao_id'].'-'.$c['id'])
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
                    return CursoTutelado::query()
                        ->whereKey($shared->curso_tutelado_tutelado_id)
                        ->with('instituicaoCurso.curso:id,nome')
                        ->get()
                        ->map(fn (CursoTutelado $ct): array => [
                            'id' => (string) $ct->getKey(),
                            'nome' => $ct->instituicaoCurso?->curso?->nome ?? $shared->curso_nome,
                            'instituicao_id' => (string) $tenant->instituicao_id,
                            'curso_id' => $ct->instituicaoCurso?->curso_id
                                ? (string) $ct->instituicaoCurso->curso_id
                                : ($shared->curso_id ? (string) $shared->curso_id : null),
                        ]);
                });

                $courses = $courses->merge($remoteCourses->all());
            });

        return $courses
            ->unique(fn (array $c): string => $c['instituicao_id'].'-'.$c['id'])
            ->values();
    }

    /**
     * @param  SupportCollection<int, array{id: string, nome: string, instituicao_id: string, curso_id: ?string}>  $courses
     * @return SupportCollection<int, array{id: string, nome: string}>
     */
    public function courseFilterOptions(SupportCollection $courses): SupportCollection
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

    public function classesByCurso(string $cursoTuteladoId): SupportCollection
    {
        return CursoClasse::query()
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->whereHas('classe', fn ($q) => $q->where('nome', '13ª'))
            ->with('classe:id,nome')
            ->orderBy('id')
            ->get()
            ->map(fn (CursoClasse $cc) => [
                'id' => $cc->id,
                'nome' => $cc->classe?->nome ?? $cc->nome,
            ]);
    }

    public function turnosByClasse(string $cursoClasseId): SupportCollection
    {
        return CursoClasseTurno::query()
            ->where('curso_classe_id', $cursoClasseId)
            ->with('turno:id,nome')
            ->get()
            ->map(fn ($cct) => [
                'id' => $cct->id,
                'nome' => $cct->turno->nome,
            ]);
    }

    public function turmasByTurno(string $cursoClasseTurnoId): SupportCollection
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
            ->map(fn (Instituicao $instituicao): array => [
                'id' => (string) $instituicao->getKey(),
                'nome' => $instituicao->nome,
            ]);

        if (! $this->isTutorInstitution($user)) {
            return $institutions;
        }

        $currentTenantId = (string) tenancy()->tenant->getTenantKey();

        $sharedLinks = $user->hasRole('Secretario do Curso')
            ? $this->crossTenantAccessService->vinculosSecretariados($user)
            : CursoTuteladoShared::query()
                ->where('tenant_tutor_id', $currentTenantId)
                ->where('status', 'activo')
                ->get();

        $remoteInstitutions = $sharedLinks
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
                ->toBase()
                ->map(fn (Aluno $aluno): array => [
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
            ->whereHas('turmas', fn ($query) => $query
                ->where('turmas.id', $turma->getKey())
                ->where('turma_aluno.activo', true))
            ->with('inscricao.candidato:id,nome')
            ->get()
            ->toBase()
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
                ->orderByPivotDesc('created_at')
                ->paginate(10, ['*'], 'page_banca'),
            'elementos' => $grupoPap->elementos()
                ->with('aluno.inscricao.candidato:id,nome,email', 'aluno:id,user_id,matricula,inscricao_id')
                ->latest('created_at')
                ->paginate(10, ['*'], 'page_elementos'),
        ];
    }

    /**
     * Prepara o trabalho PAP, as suas versões e os feedbacks para a página do grupo.
     */
    public function workDetails(
        GrupoPap $grupoPap,
        ?Instituicao $instituicaoTutora,
        ?string $nomeCurso,
        ?string $siglaTutora,
    ): ?array {
        $trabalho = $grupoPap->trabalhoPap()->with([
            'versoes.submetidoPor:id,nome',
            'versoes.feedbacks.utilizador:id,nome,instituicao_id',
            'aprovadoPor:id,nome,instituicao_id',
        ])->first();

        if (! $trabalho) {
            return null;
        }

        return [
            'id' => $trabalho->id,
            'status' => $trabalho->status,
            'data_aprovacao' => $trabalho->data_aprovacao?->toIso8601String(),
            'aprovado_por' => $this->rotuloDecisor(
                $trabalho->aprovadoPor,
                $trabalho->aprovado_por_externo_tenant_id,
                $instituicaoTutora,
                $siglaTutora,
                $nomeCurso,
            ),
            'versoes' => $trabalho->versoes->map(fn ($versao): array => [
                'id' => $versao->id,
                'numero_versao' => $versao->numero_versao,
                'nome_original' => $versao->nome_original,
                'status_quando_submetido' => $versao->status_quando_submetido,
                'submetido_por' => $versao->submetidoPor?->nome,
                'created_at' => $versao->created_at?->toIso8601String(),
                'feedbacks' => $versao->feedbacks->map(fn ($feedback): array => [
                    'id' => $feedback->id,
                    'tipo' => $feedback->tipo,
                    'comentario' => $feedback->comentario,
                    'utilizador' => in_array($feedback->tipo, ['correcao_coordenacao', 'aprovacao_coordenacao', 'reprovacao_coordenacao'], true)
                        ? $this->rotuloDecisor($feedback->utilizador, $feedback->utilizador_externo_tenant_id, $instituicaoTutora, $siglaTutora, $nomeCurso)
                        : $feedback->utilizador?->nome,
                    'created_at' => $feedback->created_at?->toIso8601String(),
                    'tem_ficheiro_correcao' => $feedback->caminho_ficheiro_correcao !== null,
                    'nome_original_correcao' => $feedback->nome_original_correcao,
                ])->values(),
            ])->values(),
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
            $ehTutora = $item->estado_novo !== 'pendente' && (
                $item->utilizador_externo_tenant_id !== null
                || ($instituicaoTutoraId !== null && $item->utilizador?->instituicao_id === $instituicaoTutoraId)
            );

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
                        ? (filled($nomeCurso)
                            ? PapHelper::rotuloGrupoDisciplinar($nomeCurso, $siglaInstituto)
                            : ($siglaInstituto ? "Grupo disciplinar do {$siglaInstituto}" : 'Grupo disciplinar'))
                        : ($item->utilizador?->nome ?? '—'),
                ],
            ];
        })->values();
    }

    public function siglaTutoraExterna(CursoTutelado $cursoTutelado): ?string
    {
        $tenantTutorId = $cursoTutelado->cursoTuteladoShared?->tenant_tutor_id;
        $tenant = $tenantTutorId ? Tenant::query()->find($tenantTutorId) : null;

        return $tenant?->run(
            fn (): ?string => Instituicao::query()->find($tenant->instituicao_id)?->sigla
        );
    }

    private function rotuloDecisor(
        ?User $user,
        ?string $tenantExternoId,
        ?Instituicao $tutoraLocal,
        ?string $siglaTutora,
        ?string $nomeCurso,
    ): ?string {
        if ($tenantExternoId !== null && filled($nomeCurso)) {
            return PapHelper::rotuloGrupoDisciplinar($nomeCurso, $siglaTutora);
        }

        if ($user && $tutoraLocal && filled($nomeCurso)) {
            return PapHelper::nomeAprovador($user, $tutoraLocal, $nomeCurso);
        }

        return $user?->nome;
    }

    public function academicYears(): Collection
    {
        return AnoLectivo::all();
    }
}
