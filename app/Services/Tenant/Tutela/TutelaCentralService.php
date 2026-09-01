<?php

namespace App\Services\Tenant\Tutela;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Services\Tenant\Tutela\Data\InstituicaoTutoraData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Persiste vínculos de tutela exclusivamente na base central.
 */
class TutelaCentralService
{
    /**
     * Se não for editado nada, manter a tutela actual.
     */
    public function tutorAtual(CursoTutelado $cursoTutelado): ?string
    {
        if (!$cursoTutelado->curso_tutelado_shared_id) {
            return null;
        }

        return CursoTuteladoShared::on($this->centralConnection())
            ->whereKey($cursoTutelado->curso_tutelado_shared_id)
            ->value('tenant_tutor_id');
    }

    /**
     * Cria ou actualiza um vínculo de tutela na base central.
     *
     * A transação usa exclusivamente a conexão central. Um vínculo existente
     * é bloqueado durante a procura para evitar actualizações concorrentes.
     */
    public function criarOuActualizarVinculo(
        CursoTutelado $cursoTutelado,
        InstituicaoTutoraData $instituicaoTutora
    ): CursoTuteladoShared {
        $cursoTutelado->loadMissing('instituicaoCurso.curso');

        $tenantTuteladoId = (string) tenancy()->tenant->getTenantKey();
        $centralConnection = $this->centralConnection();
        $curso = $cursoTutelado->instituicaoCurso?->curso;
        $tenantTutorId = (string) $instituicaoTutora->tenant->getTenantKey();

        Log::debug('Iniciando criação/actualização de vínculo na central', [
            'curso_tutelado_id' => $cursoTutelado->id,
            'tenant_tutor_id' => $tenantTutorId,
            'tenant_tutelado_id' => $tenantTuteladoId,
            'curso_nome' => $curso?->nome,
        ]);

        if ($tenantTutorId === $tenantTuteladoId) {
            abort(422, 'A instituição tutora deve ser diferente da instituição tutelada.');
        }

        return DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $tenantTutorId, $tenantTuteladoId, $instituicaoTutora, $curso, $centralConnection, ): CursoTuteladoShared {
            $shared = $this->findExisting(
                $cursoTutelado,
                $tenantTutorId,
                $tenantTuteladoId,
                $centralConnection,
            );

            $attributes = [
                'tenant_tutor_id' => $tenantTutorId,
                'tenant_tutelado_id' => $tenantTuteladoId,
                'curso_tutelado_tutelado_id' => $cursoTutelado->getKey(),
                'tenant_tutor_nome' => $instituicaoTutora->instituicao->nome,
                'curso_nome' => $curso?->nome ?? 'Curso sem nome',
                'duracao_anos' => $cursoTutelado->instituicaoCurso?->duracao_anos ?? $curso?->duracao_anos ?? 1,
                'status' => TutelaStatus::PENDENTE,
            ];

            if ($shared) {
                Log::info('Actualizando vínculo existente na central', [
                    'shared_id' => $shared->id,
                    'tenant_tutor_id' => $tenantTutorId,
                    'tenant_tutelado_id' => $tenantTuteladoId,
                    'status_anterior' => $shared->status->value,
                    'status_novo' => TutelaStatus::PENDENTE->value,
                ]);
                $shared->update($attributes);

                return $shared->refresh();
            }

            $newSharedId = (string) Str::uuid7();
            Log::info('Criando novo vínculo na central', [
                'shared_id' => $newSharedId,
                'tenant_tutor_id' => $tenantTutorId,
                'tenant_tutelado_id' => $tenantTuteladoId,
                'curso_tutelado_id' => $cursoTutelado->id,
                'status' => TutelaStatus::PENDENTE->value,
            ]);

            return CursoTuteladoShared::on($centralConnection)->create([
                'id' => $newSharedId,
                ...$attributes,
            ]);
        });
    }

    /**
     * Remove o vínculo central associado ao curso, quando existir.
     *
     * Não altera o curso local; essa responsabilidade pertence ao tenant service.
     */
    public function removerVinculo(CursoTutelado $cursoTutelado): void
    {
        if (!$cursoTutelado->curso_tutelado_shared_id) {
            Log::debug('Vínculo não encontrado; remoção ignorada', [
                'curso_tutelado_id' => $cursoTutelado->id,
            ]);

            return;
        }

        $centralConnection = $this->centralConnection();

        DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $centralConnection): void {
            Log::info('Removendo vínculo da central', [
                'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
                'curso_tutelado_id' => $cursoTutelado->id,
            ]);

            CursoTuteladoShared::on($centralConnection)
                ->whereKey($cursoTutelado->curso_tutelado_shared_id)
                ->delete();

            Log::info('Vínculo removido com sucesso da central', [
                'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
            ]);
        });
    }

    /**
     * Marca como encerrado o vínculo central associado ao curso.
     *
     * Não altera grupos PAP nem dados do tenant; essas alterações ocorrem fora
     * desta conexão, no serviço de tenant.
     */
    public function encerrarTutela(CursoTutelado $cursoTutelado): void
    {
        if (!$cursoTutelado->curso_tutelado_shared_id) {
            Log::debug('Vínculo não encontrado; encerramento ignorado', [
                'curso_tutelado_id' => $cursoTutelado->id,
            ]);

            return;
        }

        $centralConnection = $this->centralConnection();

        DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $centralConnection): void {
            Log::info('Encerrando vínculo na central', [
                'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
                'curso_tutelado_id' => $cursoTutelado->id,
                'status_novo' => TutelaStatus::ENCERRADO->value,
            ]);

            CursoTuteladoShared::on($centralConnection)
                ->whereKey($cursoTutelado->curso_tutelado_shared_id)
                ->update(['status' => TutelaStatus::ENCERRADO]);

            Log::info('Vínculo encerrado com sucesso na central', [
                'shared_id' => $cursoTutelado->curso_tutelado_shared_id,
            ]);
        });
    }

    /**
     * Localiza o vínculo actual e bloqueia-o durante a transação central.
     *
     * Primeiro usa o ID já associado ao curso; sem esse ID, usa a combinação
     * de tenant tutor, tenant tutelado e curso tutelado.
     */
    private function findExisting(
        CursoTutelado $cursoTutelado,
        string $tenantTutorId,
        string $tenantTuteladoId,
        string $centralConnection,
    ): ?CursoTuteladoShared {
        if ($cursoTutelado->curso_tutelado_shared_id) {
            return CursoTuteladoShared::on($centralConnection)
                ->lockForUpdate()
                ->find($cursoTutelado->curso_tutelado_shared_id);
        }

        return CursoTuteladoShared::on($centralConnection)
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('tenant_tutelado_id', $tenantTuteladoId)
            ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
            ->lockForUpdate()
            ->first();
    }

    /**
     * Devolve o nome configurado para a conexão central.
     */
    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }
}
