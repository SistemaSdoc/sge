<?php

namespace App\Services\Tenant\Pauta\Index;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexContext;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Consulta cursos e turmas nos tenants tutelados com vínculos visíveis.
 */
final class RemotePautaIndexProvider
{
    /**
     * Injeta o mapper usado antes de sair do contexto de cada tenant remoto.
     */
    public function __construct(private readonly PautaIndexTurmaMapper $turmaMapper) {}

    /**
     * Obtém e agrega os dados remotos permitidos ao utilizador.
     */
    public function obter(User $user, PautaIndexContext $contexto): PautaIndexData
    {
        $vinculosVisiveis = $this->obterVinculosVisiveis($user, $contexto);

        $instituicoes = collect();

        $cursos = collect();

        $turmas = collect();

        // Agrupar por tenant evita alternar o contexto para cada vínculo individual.
        foreach ($vinculosVisiveis->groupBy('tenant_tutelado_id') as $tenantId => $vinculosTenant) {
            /** @var Collection<int, CursoTuteladoShared> $vinculosTenant */
            $tenantTutelado = Tenant::query()->find($tenantId);

            if ($tenantTutelado === null) {
                continue;
            }

            $dadosTenant = $tenantTutelado->run(fn (): PautaIndexData => $this->obterDadosDoTenant(
                $tenantTutelado,
                $vinculosTenant,
                $contexto,
                $user,
            ));

            $instituicoes = $instituicoes->merge($dadosTenant->instituicoes);

            $cursos = $cursos->merge($dadosTenant->cursos);

            $turmas = $turmas->merge($dadosTenant->turmas);
        }

        return new PautaIndexData($instituicoes, $cursos, $turmas);
    }

    /**
     * Obtém vínculos activos e conserva apenas os autorizados para consulta.
     *
     * @return Collection<int, CursoTuteladoShared>
     */
    private function obterVinculosVisiveis(User $user, PautaIndexContext $contexto): Collection
    {
        /** @var Collection<int, CursoTuteladoShared> $vinculosActivos */
        $vinculosActivos = $contexto->isInstituteSecretary
            ? $contexto->secretariadoSharedLinks
            : ($contexto->isCourseSecretary ? collect() : CursoTuteladoShared::query()
                ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
                ->where('status', 'activo')
                ->get());

        $vinculosVisiveis = collect();

        foreach ($vinculosActivos as $vinculo) {
            /** @var CursoTuteladoShared $vinculo */
            if ($user->can('pautas.viewAny') || $this->ehCoordenadorDoCursoPartilhado($user, $vinculo)) {
                $vinculosVisiveis->push($vinculo);
            }
        }

        return $vinculosVisiveis;
    }

    /**
     * Consulta os dados de um tenant enquanto esse tenant está activo.
     *
     * @param  Collection<int, CursoTuteladoShared>  $vinculosTenant
     */
    private function obterDadosDoTenant(
        Tenant $tenantTutelado,
        Collection $vinculosTenant,
        PautaIndexContext $contexto,
        User $user,
    ): PautaIndexData {
        $instituicoes = $this->obterInstituicao($tenantTutelado);

        // A instituição é listada mesmo quando o filtro actual exclui os seus cursos.
        if (! $this->deveListarCursos($tenantTutelado, $contexto)) {
            return new PautaIndexData($instituicoes, collect(), collect());
        }

        $cursosRemotos = $this->consultarCursos($vinculosTenant);
        $cursos = $this->mapearCursos($cursosRemotos);
        $idsCursos = $cursosRemotos->modelKeys();

        $turmas = $idsCursos === []
            ? collect()
            : $this->obterTurmas($idsCursos, $contexto->anoLectivoId, $user);

        return new PautaIndexData($instituicoes, $cursos, $turmas);
    }

    /**
     * Obtém a instituição do tenant remoto para a lista de filtros.
     *
     * @return Collection<int, array{id: string, nome: string}>
     */
    private function obterInstituicao(Tenant $tenantTutelado): Collection
    {
        $instituicao = Instituicao::query()->find($tenantTutelado->instituicao_id);

        if ($instituicao === null) {
            return collect();
        }

        return collect([[
            'id' => (string) $instituicao->getKey(),
            'nome' => $instituicao->nome,
        ]]);
    }

    /**
     * Decide se os cursos do tenant correspondem à instituição seleccionada.
     */
    private function deveListarCursos(Tenant $tenantTutelado, PautaIndexContext $contexto): bool
    {
        return $contexto->isAllTuteladoInstitutionsFilter
            || (string) $tenantTutelado->instituicao_id === $contexto->instituicaoIdFiltro;
    }

    /**
     * Consulta os modelos de cursos associados aos vínculos do tenant.
     *
     * @param  Collection<int, CursoTuteladoShared>  $vinculosTenant
     * @return EloquentCollection<int, CursoTutelado>
     */
    private function consultarCursos(Collection $vinculosTenant): EloquentCollection
    {
        /** @var EloquentCollection<int, CursoTutelado> $cursosRemotos */
        $cursosRemotos = CursoTutelado::query()
            ->whereIn('id', $vinculosTenant->pluck('curso_tutelado_tutelado_id'))
            ->with('instituicaoCurso.curso:id,nome')
            ->get();

        return $cursosRemotos;
    }

    /**
     * Converte os modelos remotos para as linhas de curso da listagem.
     *
     * @param  EloquentCollection<int, CursoTutelado>  $cursosRemotos
     * @return Collection<int, array{id: string, curso_key: string, nome: string, remote: bool}>
     */
    private function mapearCursos(EloquentCollection $cursosRemotos): Collection
    {
        $cursos = collect();

        foreach ($cursosRemotos as $cursoTutelado) {
            $cursos->push([
                'curso_key' => (string) ($cursoTutelado->instituicaoCurso?->curso_id ?? $cursoTutelado->getKey()),
                'id' => (string) $cursoTutelado->getKey(),
                'nome' => $cursoTutelado->instituicaoCurso?->curso?->nome ?? 'Curso sem nome',
                'remote' => true,
            ]);
        }

        return $cursos->keyBy(fn (array $curso): string => $curso['curso_key'])->values();
    }

    /**
     * Consulta e mapeia as turmas do tenant remoto enquanto o seu contexto está activo.
     *
     * @param  list<int|string>  $idsCursos
     * @return Collection<int, array{id: string, _created_at: int, nome: string, classe: string|null, turno: string|null, curso: string, curso_tutelado_id: string, curso_key: string, can: array{view_pauta: bool}}>
     */
    private function obterTurmas(array $idsCursos, string $anoLectivoId, User $user): Collection
    {
        /** @var EloquentCollection<int, Turma> $turmasRemotas */
        $turmasRemotas = Turma::query()
            ->whereHas(
                'cursoClasseTurno.cursoClasse',
                fn ($query) => $query->whereIn('curso_tutelado_id', $idsCursos),
            )
            ->where('ano_lectivo_id', $anoLectivoId)
            ->with($this->turmaRelations())
            ->latest('created_at')
            ->get();

        $turmas = collect();

        foreach ($turmasRemotas as $turma) {
            // O mapper corre no tenant remoto para materializar as relações carregadas nesse contexto.
            $turmas->push($this->turmaMapper->mapear($turma, $user, true));
        }

        return $turmas;
    }

    /**
     * Define as relações necessárias para formatar as turmas remotas.
     *
     * @return list<string>
     */
    private function turmaRelations(): array
    {
        return $this->turmaMapper->relacoesNecessarias();
    }

    /**
     * Verifica se o utilizador coordena o curso associado ao vínculo.
     */
    private function ehCoordenadorDoCursoPartilhado(User $user, CursoTuteladoShared $shared): bool
    {
        if ($user->professor?->id === null || $shared->curso_id === null) {
            return false;
        }

        return $user->professor->cursosTutelados()
            ->whereHas('instituicaoCurso', fn ($query) => $query->where('curso_id', $shared->curso_id))
            ->wherePivot('coordenador', true)
            ->exists();
    }
}
