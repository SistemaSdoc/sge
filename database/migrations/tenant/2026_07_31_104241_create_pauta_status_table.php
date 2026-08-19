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
        Schema::create('pauta_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('turma_disciplina_professor_id')
                ->constrained('turma_disciplina_professor');
            $table->unsignedTinyInteger('periodo');
            $table->enum('status', ['rascunho', 'finalizada', 'expirada'])->default('rascunho');
            $table->boolean('finalizada_automaticamente')->default(false);
            $table->timestamp('notificado_em')->nullable();
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamps();

            $table->unique(['turma_disciplina_professor_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pauta_status');
    }
};
