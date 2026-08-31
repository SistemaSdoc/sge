<?php

namespace App\Services\Tenant\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Executa alterações de tutela nas bases dos tenants.
 */
class TutelaTenantService
{
    /**
     * Executa uma operação no tenant tutelado após validar o vínculo activo.
     *
     * A conexão central é usada apenas para validar o vínculo e localizar o tenant
     * de destino; a operação é executada dentro de `tenant->run(...)`.
     *
     * @param  Closure(mixed ...$parameters): mixed  $operation  Operação executada no tenant tutelado.
     */
    public function executarNoTenantTutelado(
        string $cursoTuteladoId,
        string $tenantTutorId,
        Closure $operation
    ): mixed {
        $centralConnection = $this->centralConnection();
        $shared = CursoTuteladoShared::on($centralConnection)
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('curso_tutelado_tutelado_id', $cursoTuteladoId)
            ->where('status', TutelaStatus::ACTIVO)
            ->firstOrFail();
        $tenantTutelado = Tenant::on($centralConnection)->findOrFail($shared->tenant_tutelado_id);

        return $tenantTutelado->run($operation);
    }

    /**
     * Associa um vínculo pendente ao curso no tenant tutelado actual.
     *
     * Confirma o ownership do vínculo e do curso antes de escrever na base tenant.
     */
    public function associarTutelaExterna(
        CursoTutelado $cursoTutelado,
        CursoTuteladoShared $shared
    ): void {
        $tenant = $this->tenant();
        $tenantId = (string) $tenant->getTenantKey();

        Log::debug('Iniciando associação de tutela no tenant', [
            'shared_id' => $shared->id,
            'tenant_tutelado_id' => $tenantId,
            'curso_tutelado_id' => $cursoTutelado->id,
            'status_vinculo' => $shared->status->value,
        ]);

        if ((string) $shared->tenant_tutelado_id !== $tenantId) {
            Log::error('Validação falhou: vínculo não pertence ao tenant actual', [
                'shared_id' => $shared->id,
                'tenant_esperado' => $tenantId,
                'tenant_vinculo' => (string) $shared->tenant_tutelado_id,
            ]);
            throw new \LogicException('O vínculo de tutela não pertence ao tenant actual.');
        }

        if ($shared->status !== TutelaStatus::PENDENTE) {
            Log::error('Validação falhou: vínculo não está pendente', [
                'shared_id' => $shared->id,
                'status_atual' => $shared->status->value,
            ]);
            throw new \LogicException('A tutela deve estar pendente antes da associação local.');
        }

        $tenant->run(function () use ($cursoTutelado, $shared): void {
            CursoTutelado::query()->findOrFail($cursoTutelado->getKey());

            Log::info('Associando tutela externa no tenant', [
                'shared_id' => $shared->id,
                'tenant_id' => (string) tenancy()->tenant->getTenantKey(),
                'curso_tutelado_id' => $cursoTutelado->id,
                'tipo_tutela' => 'externa',
            ]);

            $cursoTutelado->forceFill([
                'instituicao_tutora_id' => null,
                'tipo_tutela' => 'externa',
                'curso_tutelado_shared_id' => $shared->getKey(),
            ])->save();

            Log::info('Tutela externa associada com sucesso no tenant', [
                'shared_id' => $shared->id,
                'curso_tutelado_id' => $cursoTutelado->id,
            ]);
        });
    }

    /**
     * Converte o curso para tutela própria na base do tenant actual.
     *
     * A instituição indicada também é procurada no tenant actual antes da alteração.
     */
    public function converterParaTutelaPropria(
        CursoTutelado $cursoTutelado,
        string $instituicaoId
    ): void {
        $tenant = $this->tenant();
        $tenantId = (string) $tenant->getTenantKey();

        Log::info('Iniciando conversão para tutela própria', [
            'tenant_id' => $tenantId,
            'curso_tutelado_id' => $cursoTutelado->id,
            'instituicao_id' => $instituicaoId,
            'tipo_tutela_anterior' => $cursoTutelado->tipo_tutela,
        ]);

        $tenant->run(function () use ($cursoTutelado, $instituicaoId): void {
            Instituicao::query()->findOrFail($instituicaoId);

            $cursoTutelado->forceFill([
                'instituicao_tutora_id' => $instituicaoId,
                'tipo_tutela' => 'propria',
                'curso_tutelado_shared_id' => null,
            ])->save();

            Log::info('Conversão para tutela própria completada', [
                'tenant_id' => (string) tenancy()->tenant->getTenantKey(),
                'curso_tutelado_id' => $cursoTutelado->id,
                'instituicao_id' => $instituicaoId,
                'tipo_tutela_novo' => 'propria',
            ]);
        });
    }

    /**
     * Arquiva os grupos PAP ainda abertos associados ao curso no tenant actual.
     */
    public function arquivarGruposPap(CursoTutelado $cursoTutelado): void
    {
        $this->tenant()->run(function () use ($cursoTutelado): void {
            Log::info('Iniciando arquivamento de grupos PAP', [
                'tenant_id' => (string) tenancy()->tenant->getTenantKey(),
                'curso_tutelado_id' => $cursoTutelado->id,
            ]);

            $updated = GrupoPap::query()
                ->whereHas(
                    'turma.cursoClasseTurno.cursoClasse',
                    fn ($query) => $query->whereKey($cursoTutelado->getKey())
                )
                ->where('status', '!=', 'concluido')
                ->where('status_aprovacao', '!=', 'arquivado')
                ->update([
                    'status_aprovacao' => 'arquivado',
                    'encerrado_em' => now(),
                ]);

            Log::info('Arquivamento de grupos PAP completado', [
                'tenant_id' => (string) tenancy()->tenant->getTenantKey(),
                'curso_tutelado_id' => $cursoTutelado->id,
                'grupos_arquivados' => $updated,
            ]);
        });
    }

    /**
     * Devolve o tenant actualmente inicializado ou falha com contexto inválido.
     */
    private function tenant(): Tenant
    {
        $tenant = tenancy()->tenant;

        if (! $tenant instanceof Tenant) {
            throw new \LogicException('O tenancy deve estar inicializado para executar uma operação tenant.');
        }

        return $tenant;
    }

    /**
     * Devolve o nome configurado para a conexão central.
     */
    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }
}
