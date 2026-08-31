<?php

namespace App\Services\Tenant\Tutela;

use App\Enums\TenantStatus;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Services\Central\TenantService;
use App\Services\Tenant\Tutela\Data\InstituicaoTutoraData;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Valida tenants e instituições elegíveis para tutela externa.
 */
class TutelaValidator
{
    /**
     * Recebe o serviço que localiza a instituição de cada tenant.
     */
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * Valida o tenant e a instituição que podem actuar como tutores.
     *
     * A instituição actual deve ser um colégio, o tenant tutor deve estar activo
     * ou em período de teste, e a instituição tutora deve ser um instituto.
     *
     * @throws HttpExceptionInterface
     */
    public function validarTutelaExterna(
        Instituicao $instituicaoTutelada,
        string $tenantTutorId
    ): InstituicaoTutoraData {
        $tenantTuteladoId = (string) tenancy()->tenant->getTenantKey();

        Log::info('Iniciando validação de tutela externa', [
            'tenant_tutelado_id' => $tenantTuteladoId,
            'tenant_tutor_id' => $tenantTutorId,
            'instituicao_tutelada_id' => $instituicaoTutelada->id,
            'instituicao_tipo' => $instituicaoTutelada->tipo,
        ]);

        $tenantTutor = Tenant::query()->findOrFail($tenantTutorId);

        if ($instituicaoTutelada->tipo !== 'colegio') {
            Log::warning('Validação falhou: instituição tutelada não é colégio', [
                'tenant_tutelado_id' => $tenantTuteladoId,
                'instituicao_tipo' => $instituicaoTutelada->tipo,
            ]);
            abort(422, 'Apenas colégios podem ter tutela externa.');
        }

        if ($tenantTutorId === $tenantTuteladoId) {
            Log::warning('Validação falhou: tutor e tutelado são o mesmo tenant', [
                'tenant_id' => $tenantTutorId,
            ]);
            abort(422, 'A instituição tutora deve ser diferente da instituição tutelada.');
        }

        if (! in_array($tenantTutor->status, [TenantStatus::ACTIVE, TenantStatus::TRIAL], true)) {
            Log::warning('Validação falhou: tenant tutor não está activo', [
                'tenant_tutor_id' => $tenantTutorId,
                'tenant_status' => $tenantTutor->status->value,
            ]);
            abort(422, 'A instituição tutora não está disponível.');
        }

        $instituicaoTutora = $this->tenantService->getInstituicao($tenantTutor);

        if ($instituicaoTutora?->tipo !== 'instituto') {
            Log::warning('Validação falhou: instituição tutora não é do tipo instituto', [
                'tenant_tutor_id' => $tenantTutorId,
                'instituicao_tipo' => $instituicaoTutora?->tipo,
            ]);
            abort(422, 'A instituição tutora deve ser do tipo instituto.');
        }

        Log::info('Validação de tutela externa completada com sucesso', [
            'tenant_tutelado_id' => $tenantTuteladoId,
            'tenant_tutor_id' => $tenantTutorId,
            'instituicao_tutora_id' => $instituicaoTutora->id,
        ]);

        return new InstituicaoTutoraData($tenantTutor, $instituicaoTutora);
    }
}
