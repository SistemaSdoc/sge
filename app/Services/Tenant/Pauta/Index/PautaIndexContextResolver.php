<?php

namespace App\Services\Tenant\Pauta\Index;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use App\Services\Tenant\AnoLectivo\AnoLectivoResolverService;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Pauta\Index\Data\PautaIndexContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Normaliza os filtros da listagem e valida o âmbito pedido pelo utilizador.
 */
final class PautaIndexContextResolver
{
    /**
     * Injeta os serviços que resolvem o ano lectivo e os vínculos partilhados.
     */
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private readonly CrossTenantAccessService $crossTenantAccessService,
    ) {}

    /**
     * Resolve os filtros e identifica os cursos acessíveis ao utilizador.
     */
    public function resolver(User $user, Request $request): PautaIndexContext
    {
        $filtros = $request->query();

        $instituicaoId = (string) $user->instituicao_id;

        $instituicaoIdFiltro = (string) ($filtros['instituicao_id'] ?? $instituicaoId);

        $cursoTuteladoIdFiltro = $filtros['curso_tutelado_id'] ?? null;

        $anoLectivoId = filled($filtros['ano_lectivo_id'] ?? null)
            ? (string) $filtros['ano_lectivo_id']
            : (string) $this->anoLectivoResolverService->obterAnoLectivoDefault();

        $isProfessor = $user->hasRole('Professor');

        $isCourseSecretary = $user->hasRole('Secretario do Curso');

        $isInstituteSecretary = $isCourseSecretary && $user->instituicao?->tipo === 'instituto';

        $professorId = $user->professor?->id;

        $secretariadoCourseIds = $isCourseSecretary
            ? $user->cursosSecretariados()->pluck('curso_tutelado.id')->map(fn ($id): string => (string) $id)->all()
            : [];

        $secretariadoSharedLinks = $isInstituteSecretary
            ? $this->crossTenantAccessService->vinculosSecretariados($user)
            : collect();

        $secretariadoRemoteCourseIds = $secretariadoSharedLinks
            ->pluck('curso_tutelado_tutelado_id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        $isAllTuteladoInstitutionsFilter = $isInstituteSecretary
            && $instituicaoIdFiltro === $instituicaoId;

        // Valida o âmbito solicitado antes de qualquer consulta a outro tenant.
        $this->validarAcessoSeleccionado(
            $user,
            $instituicaoId,
            $instituicaoIdFiltro,
            $cursoTuteladoIdFiltro,
            $isCourseSecretary,
            $isInstituteSecretary,
            $isAllTuteladoInstitutionsFilter,
            $secretariadoCourseIds,
            $secretariadoRemoteCourseIds,
            $secretariadoSharedLinks,
        );

        $idsCursosCoordenados = $isProfessor
            ? ($user->professor?->cursosTutelados()->wherePivot('coordenador', true)->get()->modelKeys() ?? [])
            : [];

        return new PautaIndexContext(
            filtros: $filtros,
            instituicaoId: $instituicaoId,
            instituicaoIdFiltro: $instituicaoIdFiltro,
            cursoTuteladoIdFiltro: $cursoTuteladoIdFiltro,
            anoLectivoId: $anoLectivoId,
            isProfessor: $isProfessor,
            isCourseSecretary: $isCourseSecretary,
            isInstituteSecretary: $isInstituteSecretary,
            professorId: $professorId,
            secretariadoCourseIds: $secretariadoCourseIds,
            secretariadoSharedLinks: $secretariadoSharedLinks,
            secretariadoRemoteCourseIds: $secretariadoRemoteCourseIds,
            isAllTuteladoInstitutionsFilter: $isAllTuteladoInstitutionsFilter,
            idsCursosCoordenados: $idsCursosCoordenados,
        );
    }

    /**
     * Impede que secretários consultem instituições ou cursos fora do seu âmbito.
     *
     * @param  list<string>  $secretariadoCourseIds
     * @param  list<string>  $secretariadoRemoteCourseIds
     * @param  Collection<int, CursoTuteladoShared>  $secretariadoSharedLinks
     */
    private function validarAcessoSeleccionado(
        User $user,
        string $instituicaoId,
        string $instituicaoIdFiltro,
        mixed $cursoTuteladoIdFiltro,
        bool $isCourseSecretary,
        bool $isInstituteSecretary,
        bool $isAllTuteladoInstitutionsFilter,
        array $secretariadoCourseIds,
        array $secretariadoRemoteCourseIds,
        Collection $secretariadoSharedLinks,
    ): void {
        if (! $isCourseSecretary) {
            return;
        }

        if (! $isInstituteSecretary) {
            abort_unless($instituicaoIdFiltro === $instituicaoId, 404);
        } elseif (! $isAllTuteladoInstitutionsFilter) {
            $allowedInstitutionIds = $secretariadoSharedLinks
                ->map(fn (CursoTuteladoShared $shared) => Tenant::query()->find($shared->tenant_tutelado_id)?->instituicao_id)
                ->filter()
                ->map(fn ($id): string => (string) $id)
                ->all();

            abort_unless(
                $instituicaoIdFiltro === $instituicaoId || \in_array($instituicaoIdFiltro, $allowedInstitutionIds, true),
                404,
            );
        }

        if (filled($cursoTuteladoIdFiltro)) {
            abort_unless(
                \in_array((string) $cursoTuteladoIdFiltro, $secretariadoCourseIds, true)
                    || \in_array((string) $cursoTuteladoIdFiltro, $secretariadoRemoteCourseIds, true),
                404,
            );
        }
    }
}
