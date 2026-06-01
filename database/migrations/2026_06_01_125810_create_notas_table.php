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
        Schema::create('notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('turma_aluno_id');
            $table->foreign('turma_aluno_id')->references('id')->on('turma_aluno');
            $table->uuid('turma_disciplina_professor_id');
            $table->foreign('turma_disciplina_professor_id')->references('id')->on('turma_disciplina_professor');
            $table->tinyInteger('periodo'); // 1, 2, 3
            $table->integer('faltas')->default(0);
            $table->enum('situacao_trimestral', ['APTO', 'N/APTO', 'recuperacao', 'EEF'])
                ->nullable();
            $table->enum('situacao_anual', ['APTO', 'N/APTO', 'EEF'])
                ->nullable();
            $table->text('observacao')->nullable();

            // Notas
            $table->decimal('mac', 5, 2)->nullable();
            $table->decimal('nota_prova_professor', 5, 2)->nullable();
            $table->decimal('nota_prova_trimestral', 5, 2)->nullable();
            $table->decimal('media_trimestral', 5, 2)->nullable();
            $table->decimal('media_final', 5, 2)->nullable();
            $table->timestamps();

            $table->unique([
                'turma_aluno_id',
                'turma_disciplina_professor_id',
                'periodo'
            ], 'unique_nota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
