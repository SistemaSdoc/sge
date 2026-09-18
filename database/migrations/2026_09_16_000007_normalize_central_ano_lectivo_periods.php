<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ano_lectivos')->orderBy('ano_inicio')->get()->each(function (object $ano): void {
            $inicio = Carbon::create(
                (int) $ano->ano_inicio,
                (int) config('ano-lectivo.inicio_mes'),
                (int) config('ano-lectivo.inicio_dia'),
                (int) config('ano-lectivo.inicio_hora'),
                (int) config('ano-lectivo.inicio_minuto'),
                0,
            );

            $fim = Carbon::create(
                (int) $ano->ano_inicio + 1,
                (int) config('ano-lectivo.fim_mes'),
                (int) config('ano-lectivo.fim_dia'),
                (int) config('ano-lectivo.fim_hora'),
                (int) config('ano-lectivo.fim_minuto'),
                59,
            );

            DB::table('ano_lectivos')
                ->where('id', $ano->id)
                ->update([
                    'nome' => "{$ano->ano_inicio}/".((int) $ano->ano_inicio + 1),
                    'data_inicio' => $inicio,
                    'data_fim' => $fim,
                ]);
        });
    }

    public function down(): void
    {
        // Official periods replace legacy/test dates intentionally.
    }
};
