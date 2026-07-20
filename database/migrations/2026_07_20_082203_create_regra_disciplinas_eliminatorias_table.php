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
        Schema::create('regra_disciplinas_eliminatorias', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('regra_avaliacao_id')
                ->constrained('regras_avaliacao')
                ->cascadeOnDelete(); // disciplinas eliminatórias morrem com a regra

            $table->foreignUuid('disciplina_id')->constrained('disciplinas');

            $table->decimal('nota_minima', 4, 1); // nota mínima obrigatória nesta disciplina

            $table->timestamps();

            $table->unique(['regra_avaliacao_id', 'disciplina_id']); // sem duplicados por regra
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regra_disciplinas_eliminatorias');
    }
};
