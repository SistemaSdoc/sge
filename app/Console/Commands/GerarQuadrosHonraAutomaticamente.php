<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Central\AnoLectivo;
use App\Models\Central\Tenant as CentralTenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Services\Tenant\QuadroHonra\SincronizarQuadrosHonraTrimestrais;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('quadros-honra:gerar-automaticos')]
#[Description('Gera os quadros de honra dos trimestres com todas as pautas fechadas')]
class GerarQuadrosHonraAutomaticamente extends Command
{
    public function handle(SincronizarQuadrosHonraTrimestrais $sincronizar): int
    {
        $anoLectivo = AnoLectivo::activo();

        if (! $anoLectivo) {
            $this->info('Não existe um ano lectivo activo.');

            return self::SUCCESS;
        }

        CentralTenant::query()
            ->whereIn('status', [TenantStatus::ACTIVE->value, TenantStatus::TRIAL->value])
            ->each(function (CentralTenant $tenant) use ($anoLectivo, $sincronizar): void {
                tenancy()->initialize($tenant);

                try {
                    foreach (Instituicao::query()->pluck('id') as $instituicaoId) {
                        foreach ([1, 2, 3] as $periodo) {
                            if (
                                ! $this->pautasEstaoFechadas(
                                    $instituicaoId,
                                    (string) $anoLectivo->getKey(),
                                    $periodo,
                                )
                            ) {
                                continue;
                            }

                            $quantidade = $sincronizar->sincronizar(
                                $instituicaoId,
                                (string) $anoLectivo->getKey(),
                                $periodo,
                            );

                            $this->info(
                                "Tenant {$tenant->getKey()}: {$quantidade} quadro(s) actualizados para o {$periodo}º trimestre."
                            );
                        }
                    }
                } finally {
                    tenancy()->end();
                }
            });

        return self::SUCCESS;
    }

    private function pautasEstaoFechadas(string $instituicaoId, string $anoLectivoId, int $periodo): bool
    {
        $turmasDisciplinas = TurmaDisciplinaProfessor::query()
            ->whereHas('turma', fn (Builder $query) => $query->where('ano_lectivo_id', $anoLectivoId))
            ->whereHas(
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn (Builder $query) => $query->where('instituicao_id', $instituicaoId),
            );

        if (! (clone $turmasDisciplinas)->exists()) {
            return false;
        }

        return ! (clone $turmasDisciplinas)
            ->whereDoesntHave(
                'pautaStatuses',
                fn (Builder $query) => $query
                    ->where('periodo', $periodo)
                    ->whereIn('status', ['finalizada', 'expirada']),
            )
            ->exists();
    }
}
