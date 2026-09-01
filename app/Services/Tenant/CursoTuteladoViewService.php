<?php

namespace App\Services\Tenant;

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
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
            ->with([
                'curso:id,nome',
                'cursoTutelado.instituicaoTutora:id,nome',
                'cursoTutelado.cursoTuteladoShared:id,tenant_tutor_nome,tenant_tutor_id',
            ])
            ->paginate(10)
            ->through(fn ($instituicaoCurso): array => [
                'id' => $instituicaoCurso->cursoTutelado->id,
                'nome' => $instituicaoCurso->curso->nome,
                'instituicao_tutora' => $instituicaoCurso->cursoTutelado?->instituicaoTutora?->nome
                    ?? $instituicaoCurso->cursoTutelado?->cursoTuteladoShared?->tenant_tutor_nome
                    ?? $instituicaoCurso->cursoTutelado?->cursoTuteladoShared?->tenant_tutor_id,
                'can' => [
                    'view' => $user->can('view', $instituicaoCurso->cursoTutelado),
                    'update' => $user->can('update', $instituicaoCurso->cursoTutelado),
                    'delete' => $user->can('delete', $instituicaoCurso->cursoTutelado),
                ],
            ]);
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

        $cursosJaAssociados = InstituicaoCurso::query()
            ->when(
                $instituicao->tipo === 'instituto',
                fn ($query) => $query->whereHas('instituicao', fn ($instituicaoQuery) => $instituicaoQuery->where('tipo', 'colegio')),
                fn ($query) => $query->where('instituicao_id', $instituicao->getKey())
            )
            ->pluck('curso_id');
        $cursos = Curso::query()
            ->select('id', 'nome')
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
    public function editOptions(Instituicao $instituicao): array
    {
        return [
            'classes' => Classe::query()->select('id', 'nome')->orderBy('nome')->get(),
            'niveisEnsino' => NivelEnsino::query()->select('id', 'nome')->orderBy('nome')->get(),
            'tenantsTutores' => $instituicao->tipo === 'colegio'
                ? $this->tenantService->getAvailableTutors((string) tenancy()->tenant->getTenantKey())
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
