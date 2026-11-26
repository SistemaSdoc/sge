<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_provas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ano_lectivo_id');
            $table->uuid('curso_classe_turno_id');
            $table->foreign('curso_classe_turno_id')->references('id')->on('curso_classe_turno');
            $table->enum('tipo', ['trimestral', 'recurso', 'exame_especial']);
            $table->tinyInteger('trimestre')->unsigned()->nullable();
            $table->boolean('ativo')->default(false);
            $table->timestamps();

            $table->unique(
                ['ano_lectivo_id', 'curso_classe_turno_id', 'tipo', 'trimestre'],
                'calendario_provas_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_provas');
    }
};
