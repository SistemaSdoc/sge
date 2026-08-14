<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classe_turno_disciplina_horarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('classe_turno_disciplina_id');
            $table->tinyInteger('dia_semana'); // 1 = segunda, 7 = domingo
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();

            $table->foreign('classe_turno_disciplina_id', 'fk_ctdh_ctd')
                ->references('id')
                ->on('classe_turno_disciplina')
                ->onDelete('cascade');

            $table->index(['classe_turno_disciplina_id', 'dia_semana'], 'idx_ctdh_ctd_dia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_turno_disciplina_horarios');
    }
};
