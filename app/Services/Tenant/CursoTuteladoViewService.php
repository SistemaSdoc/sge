<?php

namespace App\Services\Tenant;

use App\Enums\TutelaStatus;
use App\Models\Central\Curso;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Models\Tenant\User;
use App\Services\Central\TenantService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Prepara consultas e dados das páginas de cursos tutelados.
 */
class CursoTuteladoViewService
{
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * Lista os cursos da instituição com as permissões do utilizador.
     */
    public function index(Instituicao $instituicao, User $user): LengthAwarePaginator
    {
        return $instituicao->instituicaoCursos()
            ->has('cursoTutelado')
            ->with([
                'curso:id,nome',
                'cursoTutelado.instituicaoTutora:id,nome',
                'cursoTutelado.cursoTuteladoShared:id,status,tenant_tutor_nome,tenant_tutor_id',
            ])
            ->paginate(10)
            ->through(function ($instituicaoCurso) use ($user): array {
                $cursoTutelado = $instituicaoCurso->cursoTutelado;
                $sharedActivo = $cursoTutelado ? $this->sharedActivo($cursoTutelado) : null;
                $sharedPendente = $cursoTutelado ? $this->sharedPendente($cursoTutelado) : null;
                $conversaoPendente = $cursoTutelado
                    ? $this->temConversaoPendente($sharedActivo)
                    : false;
                $sharedExibido = $sharedPendente?->status === TutelaStatus::PENDENTE
                    ? $sharedPendente
                    : $sharedActivo;
                $nomeTutor = $this->resolverNomeTutor($cursoTutelado, $sharedExibido);
                $idTutor = $this->resolverIdTutor($cursoTutelado, $sharedExibido);

                return [
                    'id' => (string) $cursoTutelado->getKey(),
                    'nome' => $instituicaoCurso->curso?->nome ?? 'Curso indisponível',
                    'status' => $conversaoPendente
                        ? TutelaStatus::PENDENTE->value
                        : ($sharedPendente?->status?->value
                            ?? $sharedActivo?->status?->value
                            ?? $cursoTutelado?->cursoTuteladoShared?->status?->value
                            ?? $cursoTutelado?->cursoTuteladoShared?->status
                            ?? ($cursoTutelado?->tipo_tutela === 'propria' ? TutelaStatus::ACTIVO->value : null)),
                    'instituicao_tutora' => $nomeTutor ?? $idTutor,
                    'instituicao_tutora_pendente' => $sharedPendente?->status === TutelaStatus::PENDENTE_TROCA
                        ? $sharedPendente->tenant_tutor_nome
                        : null,
                    'can' => [
                        'view' => $this->can($user, 'view', $cursoTutelado),
                        'update' => $this->can($user, 'update', $cursoTutelado),
                        'delete' => $this->can($user, 'delete', $cursoTutelado),
                    ],
                ];
            });
    }

    private function resolverNomeTutor(?CursoTutelado $cursoTutelado, ?CursoTuteladoShared $sharedActivo): ?string
    {
        if ($sharedActivo) {
            return $sharedActivo->tenant_tutor_nome;
        }

        return $cursoTutelado?->instituicaoTutora?->nome
            ?? $cursoTutelado?->cursoTuteladoShared?->tenant_tutor_nome;
    }

    private function resolverIdTutor(?CursoTutelado $cursoTutelado, ?CursoTuteladoShared $sharedActivo): ?string
    {
        if ($sharedActivo) {
            return $sharedActivo->tenant_tutor_id;
        }

        return $cursoTutelado?->instituicaoTutora?->id
            ?? $cursoTutelado?->cursoTuteladoShared?->tenant_tutor_id;
    }

    private function sharedActivo(CursoTutelado $cursoTutelado): ?CursoTuteladoShared
    {
        if (! $cursoTutelado->getKey()) {
            return null;
        }

        return CursoTuteladoShared::on($this->centralConnection())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
            ->where('status', TutelaStatus::ACTIVO)
            ->latest('updated_at')
            ->first();
    }

    private function sharedPendente(CursoTutelado $cursoTutelado): ?CursoTuteladoShared
    {
        return CursoTuteladoShared::on($this->centralConnection())
            ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
            ->whereIn('status', [TutelaStatus::PENDENTE, TutelaStatus::PENDENTE_TROCA])
            ->latest()
            ->first();
    }

    private function temConversaoPendente(?CursoTuteladoShared $sharedActivo): bool
    {
        if (! $sharedActivo) {
            return false;
        }

        $tenant = Tenant::query()->find($sharedActivo->tenant_tutelado_id);

        if (! $tenant?->admin_user_id) {
            return false;
        }

        return User::query()
            ->find($tenant->admin_user_id)?->notifications()
            ->whereIn('data->tipo', ['conversao_tutela_propria', 'conversao_tutela_propria_pendente'])
            ->where('data->curso_tutelado_shared_id', (string) $sharedActivo->getKey())
            ->where('data->status', 'pendente')
            ->exists() ?? false;
    }

    private function can(User $user, string $ability, ?CursoTutelado $cursoTutelado): bool
    {
        if (! $cursoTutelado) {
            return false;
        }

        return $user->can($ability, $cursoTutelado);
    }

    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }

    /**
     * Obtém as opções do formulário de criação.
     *
     * @return array{classes: mixed, cursos: mixed, niveisEnsino: mixed, tenantsTutores: array}
     */
    public function createOptions(Instituicao $instituicao): array
    {
        $classes = Classe::query()->select('id', 'nome')->orderBy('nome')->get();

        $niveisEnsino = NivelEnsino::query()->select('id', 'nome')->orderBy('nome')->get();

        $cursosJaAssociados = $instituicao->instituicaoCursos()->pluck('curso_id');

        $cursos = Curso::query()
            ->select('id', 'nome')
            ->where('status', 1)
            ->whereNotIn('id', $cursosJaAssociados)
            ->orderBy('nome')
            ->get();

        return [
            'classes' => $classes,
            'cursos' => $cursos,
            'niveisEnsino' => $niveisEnsino,
            'tenantsTutores' => $instituicao->tipo === 'colegio'
                ? $this->tenantService->getAvailableTutors((string) tenancy()->tenant->getTenantKey())
                : [],
        ];
    }

    /**
     * Lista os cursos centrais oferecidos pelo instituto tutor seleccionado.
     *
     * @return array<int, array{id: string, nome: string}>
     */
    public function cursosDisponiveisParaTutor(string $tenantTutorId, Instituicao $instituicao): array
    {
        $tenantTutor = Tenant::query()->findOrFail($tenantTutorId);
        $instituicaoTutora = $this->tenantService->getInstituicao($tenantTutor);

        if (! $instituicaoTutora || $instituicaoTutora->tipo !== 'instituto') {
            return [];
        }

        $cursoIds = $tenantTutor->run(
            fn (): array => InstituicaoCurso::query()
                ->where('instituicao_id', $instituicaoTutora->getKey())
                ->pluck('curso_id')
                ->map(fn ($id): string => (string) $id)
                ->all()
        );

        return Curso::query()
            ->whereIn('id', $cursoIds)
            ->where('status', 1)
            ->whereNotIn('id', $instituicao->instituicaoCursos()->pluck('curso_id'))
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(fn (Curso $curso): array => [
                'id' => (string) $curso->getKey(),
                'nome' => $curso->nome,
            ])
            ->all();
    }

    /**
     * Carrega as relações necessárias para a página de detalhe.
     */
    public function prepareShow(CursoTutelado $cursoTutelado, string $anoLectivoId): void
    {
        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome,descricao',
            'instituicaoCurso.instituicao:id,nome',
            'instituicaoTutora:id,nome',
            'cursoClasses.classe:id,nome',
            'cursoClasses.turnos.turno:id,nome',
            'cursoClasses.turnos' => function ($query) use ($anoLectivoId): void {
                $query->with([
                    'turmas' => fn ($q) => $q->where('ano_lectivo_id', $anoLectivoId),
                    'turmas.cursoClasseTurno.turno:id,nome',
                    'turmas.cursoClasseTurno.cursoClasse.classe:id,nome',
                    'classeTurnoDisciplinas.professores',
                    'classeTurnoDisciplinas',
                ]);
            },
            'professores.user:id,nome',
        ]);
    }

    /**
     * Carrega as relações necessárias para o formulário de edição.
     */
    public function prepareEdit(CursoTutelado $cursoTutelado): void
    {
        $cursoTutelado->load([
            'instituicaoCurso.curso:id,nome',
            'instituicaoCurso',
            'instituicaoTutora:id,nome',
            'cursoTuteladoShared:id,tenant_tutor_id,tenant_tutor_nome,curso_nome,status',
            'cursoClasses.nivelEnsino:id,nome',
            'classes:id',
        ]);
    }

    /**
     * Obtém as opções do formulário de edição.
     *
     * @return array{classes: mixed, tenantsTutores: array}
     */
    public function editOptions(Instituicao $instituicao, CursoTutelado $cursoTutelado): array
    {
        $cursoId = (string) $cursoTutelado->instituicaoCurso?->curso_id;

        return [
            'classes' => Classe::query()->select('id', 'nome')->orderBy('nome')->get(),
            'niveisEnsino' => NivelEnsino::query()->select('id', 'nome')->orderBy('nome')->get(),
            'tenantsTutores' => $instituicao->tipo === 'colegio'
                ? $this->tenantService->getAvailableTutors(
                    (string) tenancy()->tenant->getTenantKey(),
                    $cursoId,
                )
                : [],
        ];
    }

    /**
     * Lista os anos lectivos para os filtros das páginas.
     */
    public function academicYears(): Collection
    {
        return AnoLectivo::query()
            ->select('id', 'nome')
            ->orderByDesc('data_inicio')
            ->get();
    }
}
