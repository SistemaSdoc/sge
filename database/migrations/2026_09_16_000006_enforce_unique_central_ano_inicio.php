<?php

use App\Models\Central\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('ano_lectivos')
            ->select('ano_inicio')
            ->groupBy('ano_inicio')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ano_inicio');

        foreach ($duplicados as $anoInicio) {
            $registos = DB::table('ano_lectivos')
                ->where('ano_inicio', $anoInicio)
                ->orderByRaw('deleted_at IS NULL DESC')
                ->orderByDesc('updated_at')
                ->get();

            $manter = $registos->first();

            foreach ($registos->skip(1) as $registo) {
                if ($this->temReferencias($registo->id)) {
                    throw new RuntimeException(
                        "Não é possível consolidar o ano {$anoInicio}: o registo {$registo->id} tem referências em tenants."
                    );
                }

                DB::table('ano_lectivos')->where('id', $registo->id)->delete();
            }

            if ($manter->deleted_at !== null) {
                DB::table('ano_lectivos')
                    ->where('id', $manter->id)
                    ->update(['deleted_at' => null]);
            }
        }

        Schema::table('ano_lectivos', function ($table): void {
            $table->dropUnique('ano_lectivos_ano_inicio_deleted_at_unique');
            $table->unique('ano_inicio', 'ano_lectivos_ano_inicio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ano_lectivos', function ($table): void {
            $table->dropUnique('ano_lectivos_ano_inicio_unique');
            $table->unique(
                ['ano_inicio', 'deleted_at'],
                'ano_lectivos_ano_inicio_deleted_at_unique'
            );
        });
    }

    private function temReferencias(string $anoLectivoId): bool
    {
        $referencias = [
            ['classe_turno_disciplina', 'ano_lectivo_id'],
            ['turmas', 'ano_lectivo_id'],
            ['inscricoes', 'ano_lectivo_id'],
            ['regras_avaliacao', 'ano_lectivo_id'],
            ['propinas', 'ano_lectivo_id'],
            ['periodo_lancamento_notas', 'ano_lectivo_id'],
            ['turma_aluno', 'ano_lectivo_id'],
            ['confirmacao_matricula', 'ano_lectivo_atual_id'],
            ['confirmacao_matricula', 'ano_lectivo_proximo_id'],
        ];

        foreach (Tenant::query()->get() as $tenant) {
            try {
                tenancy()->initialize($tenant);

                foreach ($referencias as [$tabela, $coluna]) {
                    if (Schema::hasTable($tabela)
                        && DB::table($tabela)->where($coluna, $anoLectivoId)->exists()) {
                        return true;
                    }
                }
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return false;
    }
};
