<?php

namespace App\Services\Tenant\Pauta\Show;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Localiza pautas remotas nos tenants tutelados com vínculos activos.
 */
final class RemotePautaShowProvider
{
    /**
     * Injeta o serviço de vínculos e o presenter comum das pautas.
     */
    public function __construct(
        private readonly CrossTenantAccessService $crossTenantAccessService,
        private readonly PautaShowPresenter $presenter,
    ) {}

    /**
     * Autoriza a consulta remota e procura a turma nos tenants associados.
     *
     * @return array<string, mixed>|null
     */
    public function obter(User $user, string $turmaId, Request $request): ?array
    {
        $isCourseSecretary = $user->hasRole('Secretario do Curso');

        $isInstituteSecretary = $isCourseSecretary && $user->instituicao?->tipo === 'instituto';

        abort_if($isCourseSecretary && ! $isInstituteSecretary, 404);

        // O acesso remoto exige permissão antes de carregar qualquer tenant tutelado.
        abort_unless($user->can('pautas.view'), 403);

        $vinculosActivos = $this->obterVinculosActivos($user, $isInstituteSecretary);

        foreach ($vinculosActivos->groupBy('tenant_tutelado_id') as $tenantId => $vinculosTenant) {
            /** @var Collection<int, CursoTuteladoShared> $vinculosTenant */
            $tenantTutelado = Tenant::query()->find($tenantId);

            if ($tenantTutelado === null) {
                continue;
            }

            $dadosPauta = $tenantTutelado->run(fn (): ?array => $this->procurarPautaNoTenant(
                $turmaId,
                $vinculosTenant,
                $request,
            ));

            if ($dadosPauta !== null) {
                return $dadosPauta;
            }
        }

        return null;
    }

    /**
     * Obtém os vínculos remotos adequados ao perfil do utilizador.
     *
     * @return Collection<int, CursoTuteladoShared>
     */
    private function obterVinculosActivos(User $user, bool $isInstituteSecretary): Collection
    {
        if ($isInstituteSecretary) {
            return $this->crossTenantAccessService->vinculosSecretariados($user);
        }

        return CursoTuteladoShared::query()
            ->where('tenant_tutor_id', tenancy()->tenant->getTenantKey())
            ->where('status', 'activo')
            ->get();
    }

    /**
     * Procura a turma apenas entre os cursos autorizados pelo vínculo remoto.
     *
     * @param  Collection<int, CursoTuteladoShared>  $vinculosTenant
     * @return array<string, mixed>|null
     */
    private function procurarPautaNoTenant(
        string $turmaId,
        Collection $vinculosTenant,
        Request $request,
    ): ?array {
        $turmaRemota = Turma::query()
            ->whereKey($turmaId)
            ->whereHas(
                'cursoClasseTurno.cursoClasse.cursoTutelado',
                fn ($query) => $query->whereIn(
                    'id',
                    $vinculosTenant->pluck('curso_tutelado_tutelado_id'),
                ),
            )
            ->with($this->presenter->relacoesNecessarias())
            ->first();

        if ($turmaRemota === null) {
            return null;
        }

        return $this->presenter->apresentar($turmaRemota, $request);
    }
}
