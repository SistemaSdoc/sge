<?php

namespace App\Services\Tenant\Tutela;

use App\Jobs\Tenant\Tutela\SincronizarAssociacaoTutela;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\Tutela\Data\InstituicaoTutoraData;
use Closure;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Orquestra validação, persistência e sincronização da tutela externa.
 */
class TutelaService
{
    /**
     * Recebe os serviços que executam cada parte do fluxo de tutela.
     */
    public function __construct(
        private readonly TutelaValidator $validator,
        private readonly TutelaCentralService $centralService,
        private readonly TutelaTenantService $tenantService,
        private readonly TutelaNotificationService $notificationService,
    ) {
    }

    /**
     * Valida se o colégio pode solicitar tutela externa a um instituto.
     *
     * Devolve o tenant e a instituição tutora já validados para publicação.
     *
     * @throws HttpExceptionInterface
     */
    public function validarTutelaExterna(
        Instituicao $instituicaoTutelada,
        string $tenantTutorId
    ): InstituicaoTutoraData {
        return $this->validator->validarTutelaExterna($instituicaoTutelada, $tenantTutorId);
    }

    /**
     * Executa uma operação na base do tenant onde o curso tutelado está guardado.
     *
     * O vínculo precisa de estar activo e pertencer ao instituto que iniciou a operação.
     *
     * @param  Closure(mixed ...$parameters): mixed  $operation  Operação executada no tenant tutelado.
     */
    public function executarNoTenantTutelado(
        string $cursoTuteladoId,
        string $tenantTutorId,
        Closure $operation
    ): mixed {
        return $this->tenantService->executarNoTenantTutelado($cursoTuteladoId, $tenantTutorId, $operation);
    }

    public function tutorAtual(CursoTutelado $cursoTutelado): ?string
    {
        return $this->centralService->tutorAtual($cursoTutelado);
    }

    /**
     * Publica uma solicitação de tutela para o instituto indicado.
     *
     * Cria ou actualiza o vínculo central, associa-o ao curso local e envia a notificação
     * somente depois de a associação local terminar.
     */
    public function publicarEAssociarCurso(
        CursoTutelado $cursoTutelado,
        InstituicaoTutoraData $instituicaoTutora
    ): CursoTuteladoShared {
        Log::info('Iniciando publicação e associação de tutela', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'tenant_tutelado_id' => (string) tenancy()->tenant->getTenantKey(),
            'tenant_tutor_id' => (string) $instituicaoTutora->tenant->getTenantKey(),
        ]);

        $shared = $this->centralService->criarOuActualizarVinculo($cursoTutelado, $instituicaoTutora);

        try {
            $this->tenantService->associarTutelaExterna($cursoTutelado, $shared);
        } catch (Throwable $exception) {
            Log::error('Falha ao associar tutela no tenant tutelado.', [
                'shared_id' => $shared->getKey(),
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
                'tenant_tutor_id' => $shared->tenant_tutor_id,
                'curso_tutelado_id' => $cursoTutelado->getKey(),
                'exception' => $exception,
            ]);

            Log::info('Despachando job de sincronização para recuperação', [
                'shared_id' => $shared->getKey(),
                'tenant_tutelado_id' => $shared->tenant_tutelado_id,
                'curso_tutelado_id' => $cursoTutelado->getKey(),
            ]);

            SincronizarAssociacaoTutela::dispatch(
                (string) $shared->tenant_tutelado_id,
                (string) $cursoTutelado->getKey(),
                (string) $shared->getKey(),
            );

            throw $exception;
        }

        Log::info('Associação de tutela completada; enviando notificação', [
            'shared_id' => $shared->getKey(),
            'tenant_tutor_id' => $shared->tenant_tutor_id,
        ]);

        $this->notificationService->notificarNovaSolicitacao($shared);

        Log::info('Fluxo de publicação e associação completado com sucesso', [
            'shared_id' => $shared->getKey(),
            'curso_tutelado_id' => $cursoTutelado->id,
        ]);

        return $shared;
    }

    /**
     * Remove da base central o vínculo associado ao curso.
     */
    public function removerVinculo(CursoTutelado $cursoTutelado): void
    {
        Log::info('Iniciando remoção de vínculo', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
        ]);

        $this->centralService->removerVinculo($cursoTutelado);

        Log::info('Remoção de vínculo completada', [
            'curso_tutelado_id' => $cursoTutelado->id,
        ]);
    }

    /**
     * Encerra o vínculo externo e converte o curso para tutela da própria instituição.
     */
    public function converterParaTutelaPropria(
        CursoTutelado $cursoTutelado,
        string $instituicaoId
    ): void {
        Log::info('Iniciando conversão para tutela própria', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
            'instituicao_id' => $instituicaoId,
        ]);

        $this->centralService->encerrarTutela($cursoTutelado);
        $this->tenantService->converterParaTutelaPropria($cursoTutelado, $instituicaoId);

        Log::info('Conversão para tutela própria completada', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'instituicao_id' => $instituicaoId,
        ]);
    }

    /**
     * Encerra o vínculo central e arquiva os grupos PAP ainda abertos do curso.
     */
    public function encerrarTutela(CursoTutelado $cursoTutelado): void
    {
        Log::info('Iniciando encerramento de tutela', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
            'tipo_tutela' => $cursoTutelado->tipo_tutela,
        ]);

        $this->centralService->encerrarTutela($cursoTutelado);
        $this->tenantService->arquivarGruposPap($cursoTutelado);

        Log::info('Encerramento de tutela completado', [
            'curso_tutelado_id' => $cursoTutelado->id,
        ]);
    }
}
