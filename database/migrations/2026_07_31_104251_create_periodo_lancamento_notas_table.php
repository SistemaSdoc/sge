<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('periodo_lancamento_notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instituicao_id')->constrained('instituicoes');
            $table->foreignUuid('ano_lectivo_id')->constrained('ano_lectivos');
            $table->unsignedTinyInteger('periodo'); // 1, 2 ou 3
            $table->date('data_inicio');
            $table->date('data_limite');
            $table->timestamps();

            $table->unique(['instituicao_id', 'ano_lectivo_id', 'periodo'], 'pln_inst_ano_periodo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodo_lancamento_notas');

    }
};
