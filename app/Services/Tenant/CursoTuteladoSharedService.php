<?php

namespace App\Services\Tenant;

use App\Enums\TenantStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Services\Central\TenantService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CursoTuteladoSharedService
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function validarTutelaExterna(
        Instituicao $instituicaoTutelada,
        string $tenantTutorId
    ): string {
        $tenantTuteladoId = (string) tenancy()->tenant->getTenantKey();
        $tenantTutor = Tenant::query()->findOrFail($tenantTutorId);

        if ($instituicaoTutelada->tipo !== 'colegio') {
            abort(422, 'Apenas colégios podem ter tutela externa.');
        }

        if ($tenantTutorId === $tenantTuteladoId) {
            abort(422, 'A instituição tutora deve ser diferente da instituição tutelada.');
        }

        if (! in_array($tenantTutor->status, [TenantStatus::ACTIVE, TenantStatus::TRIAL], true)) {
            abort(422, 'A instituição tutora não está disponível.');
        }

        if ($this->tenantService->getInstituicao($tenantTutor)?->tipo !== 'instituto') {
            abort(422, 'A instituição tutora deve ser do tipo instituto.');
        }

        return $this->tenantService->getInstituicao($tenantTutor)?->nome ?? $tenantTutorId;
    }

    /**
     * Executa uma operação no tenant que mantém os dados do curso tutelado.
     *
     * O vínculo é validado na base central antes da troca de conexão.
     */
    public function executarNoTenantTutelado(
        string $cursoTuteladoId,
        string $tenantTutorId,
        Closure $operation
    ): mixed {
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        $shared = CursoTuteladoShared::on($centralConnection)
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('curso_tutelado_tutelado_id', $cursoTuteladoId)
            ->where('status', 'activo')
            ->firstOrFail();

        $tenantTutelado = Tenant::on($centralConnection)->findOrFail($shared->tenant_tutelado_id);

        return $tenantTutelado->run($operation);
    }

    public function publicar(
        CursoTutelado $cursoTutelado,
        string $tenantTutorId,
        ?string $tenantTutorNome = null
    ): CursoTuteladoShared {
        $cursoTutelado->loadMissing('instituicaoCurso.curso');

        $tenantTuteladoId = (string) tenancy()->tenant->getTenantKey();
        $tenantTutor = Tenant::query()->findOrFail($tenantTutorId);

        if ($tenantTutorId === $tenantTuteladoId) {
            abort(422, 'A instituição tutora deve ser diferente da instituição tutelada.');
        }

        $curso = $cursoTutelado->instituicaoCurso?->curso;
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        return DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $tenantTutorId, $tenantTuteladoId, $tenantTutorNome, $curso, $centralConnection): CursoTuteladoShared {
            $shared = $cursoTutelado->curso_tutelado_shared_id
                ? CursoTuteladoShared::on($centralConnection)->find($cursoTutelado->curso_tutelado_shared_id)
                : CursoTuteladoShared::on($centralConnection)
                    ->where('tenant_tutor_id', $tenantTutorId)
                    ->where('tenant_tutelado_id', $tenantTuteladoId)
                    ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                    ->first();

            $attributes = [
                'tenant_tutor_id' => $tenantTutorId,
                'tenant_tutelado_id' => $tenantTuteladoId,
                'curso_tutelado_tutelado_id' => $cursoTutelado->getKey(),
                'tenant_tutor_nome' => $tenantTutorNome ?? $tenantTutorId,
                'curso_nome' => $curso?->nome ?? 'Curso sem nome',
                'duracao_anos' => $cursoTutelado->instituicaoCurso?->duracao_anos ?? $curso?->duracao_anos ?? 1,
                'status' => 'activo',
            ];

            if ($shared) {
                $shared->update($attributes);

                return $shared->refresh();
            }

            return CursoTuteladoShared::on($centralConnection)->create([
                'id' => (string) Str::uuid7(),
                ...$attributes,
            ]);
        });
    }

    public function publicarEAssociar(
        CursoTutelado $cursoTutelado,
        string $tenantTutorId,
        ?string $tenantTutorNome = null
    ): CursoTuteladoShared {
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        return DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $tenantTutorId, $tenantTutorNome): CursoTuteladoShared {
            $shared = $this->publicar($cursoTutelado, $tenantTutorId, $tenantTutorNome);

            DB::connection('tenant')->transaction(function () use ($cursoTutelado, $shared): void {
                $cursoTutelado->forceFill([
                    'instituicao_tutora_id' => null,
                    'tipo_tutela' => 'externa',
                    'curso_tutelado_shared_id' => $shared->getKey(),
                ])->save();
            });

            return $shared;
        });
    }

    public function remover(CursoTutelado $cursoTutelado): void
    {
        if (! $cursoTutelado->curso_tutelado_shared_id) {
            return;
        }

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $centralConnection): void {
            CursoTuteladoShared::on($centralConnection)
                ->whereKey($cursoTutelado->curso_tutelado_shared_id)
                ->delete();
        });
    }

    public function tornarPropria(
        CursoTutelado $cursoTutelado,
        string $instituicaoId
    ): void {
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $instituicaoId): void {
            $this->encerrar($cursoTutelado);

            DB::connection('tenant')->transaction(function () use ($cursoTutelado, $instituicaoId): void {
                $cursoTutelado->forceFill([
                    'instituicao_tutora_id' => $instituicaoId,
                    'tipo_tutela' => 'propria',
                    'curso_tutelado_shared_id' => null,
                ])->save();
            });
        });
    }

    public function encerrar(CursoTutelado $cursoTutelado): void
    {
        if (! $cursoTutelado->curso_tutelado_shared_id) {
            return;
        }

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        DB::connection($centralConnection)->transaction(function () use ($cursoTutelado, $centralConnection): void {
            CursoTuteladoShared::on($centralConnection)
                ->whereKey($cursoTutelado->curso_tutelado_shared_id)
                ->update(['status' => 'encerrado']);

            DB::connection('tenant')->transaction(function () use ($cursoTutelado): void {
                GrupoPap::query()
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
            });
        });
    }
}
