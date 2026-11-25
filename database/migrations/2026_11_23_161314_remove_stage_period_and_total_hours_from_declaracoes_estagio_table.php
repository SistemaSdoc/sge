<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('declaracoes_estagio')) {
            return;
        }

        $columns = array_values(array_filter(
            ['data_inicio', 'data_fim', 'carga_horaria_total'],
            fn (string $column): bool => Schema::hasColumn('declaracoes_estagio', $column),
        ));

        if ($columns !== []) {
            Schema::table('declaracoes_estagio', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('declaracoes_estagio')) {
            return;
        }

        $restoreDataInicio = ! Schema::hasColumn('declaracoes_estagio', 'data_inicio');
        $restoreDataFim = ! Schema::hasColumn('declaracoes_estagio', 'data_fim');
        $restoreCargaHorariaTotal = ! Schema::hasColumn('declaracoes_estagio', 'carga_horaria_total');

        if (! $restoreDataInicio && ! $restoreDataFim && ! $restoreCargaHorariaTotal) {
            return;
        }

        Schema::table('declaracoes_estagio', function (Blueprint $table) use (
            $restoreDataInicio,
            $restoreDataFim,
            $restoreCargaHorariaTotal,
        ): void {
            if ($restoreDataInicio) {
                $table->date('data_inicio')->nullable();
            }

            if ($restoreDataFim) {
                $table->date('data_fim')->nullable();
            }

            if ($restoreCargaHorariaTotal) {
                $table->decimal('carga_horaria_total', 6, 1)->nullable();
            }
        });
    }
};
