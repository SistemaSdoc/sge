<?php

namespace App\Services\Tenant\Pap\Contexto;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use App\Models\Tenant\User;
use App\Services\Tenant\CrossTenantAccessService;
use App\Services\Tenant\Tutela\TutelaService;
use Closure;

class ExecutarAcaoNoGrupoPapTutelado
{
    /**
     * Recebe os serviços de acesso entre tenants e de mudança de contexto.
     */
    public function __construct(
        private readonly CrossTenantAccessService $crossTenantAccessService,
        private readonly TutelaService $tutelaService,
    ) {}

    /**
     * Valida o acesso ao grupo e executa a operação no tenant tutelado.
     *
     * @param  Closure(GrupoPap, string): mixed  $operacao
     */
    public function executar(
        string $colegioId,
        string $cursoTuteladoId,
        string $cursoClasseId,
        string $cursoClasseTurnoId,
        string $turmaId,
        string $grupoPapId,
        User $utilizador,
        Closure $operacao,
        ?string $tenantTutoraId = null,
    ): mixed {
        $tenantTutoraId ??= (string) tenancy()->tenant->getTenantKey();
        $vinculo = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutoraId)
            ->where('curso_tutelado_tutelado_id', $cursoTuteladoId)
            ->where('status', 'activo')
            ->firstOrFail();
        $tenantTutelado = Tenant::query()->findOrFail($vinculo->tenant_tutelado_id);

        $this->crossTenantAccessService->validarAcessoAoGrupoPap(
            $utilizador,
            $tenantTutelado,
            $grupoPapId,
            (string) $vinculo->getKey(),
        );

        return $this->tutelaService->executarNoTenantTutelado(
            $cursoTuteladoId,
            $tenantTutoraId,
            function () use (
                $colegioId,
                $cursoTuteladoId,
                $cursoClasseId,
                $cursoClasseTurnoId,
                $turmaId,
                $grupoPapId,
                $operacao,
                $tenantTutoraId,
            ): mixed {
                $colegio = Instituicao::findOrFail($colegioId);
                $cursoTutelado = CursoTutelado::query()
                    ->whereKey($cursoTuteladoId)
                    ->whereHas('instituicaoCurso', fn ($query) => $query->where('instituicao_id', $colegio->id))
                    ->firstOrFail();
                $cursoClasse = CursoClasse::query()
                    ->whereKey($cursoClasseId)
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->firstOrFail();
                $cursoClasseTurno = CursoClasseTurno::query()
                    ->whereKey($cursoClasseTurnoId)
                    ->where('curso_classe_id', $cursoClasse->id)
                    ->firstOrFail();
                $turma = Turma::query()
                    ->whereKey($turmaId)
                    ->where('curso_classe_turno_id', $cursoClasseTurno->id)
                    ->firstOrFail();
                $grupoPap = GrupoPap::query()
                    ->whereKey($grupoPapId)
                    ->where('turma_id', $turma->id)
                    ->firstOrFail();

                return $operacao($grupoPap, $tenantTutoraId);
            },
        );
    }
}
