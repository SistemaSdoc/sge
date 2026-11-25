<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaracoes_estagio', function (Blueprint $table): void {
            $table->string('bi')->nullable();
            $table->string('curso')->nullable();
            $table->string('ano')->nullable();
            $table->decimal('nota', 3, 1)->nullable();
            $table->string('avaliacao')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->decimal('carga_horaria_diaria', 4, 1)->nullable();
            $table->decimal('carga_horaria_total', 6, 1)->nullable();
            $table->string('responsavel_nome')->nullable();
            $table->string('responsavel_cargo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('declaracoes_estagio', function (Blueprint $table): void {
            $table->dropColumn([
                'bi',
                'curso',
                'ano',
                'nota',
                'avaliacao',
                'data_inicio',
                'data_fim',
                'carga_horaria_diaria',
                'carga_horaria_total',
                'responsavel_nome',
                'responsavel_cargo',
            ]);
        });
    }
};
