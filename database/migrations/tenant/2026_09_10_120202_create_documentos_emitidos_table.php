<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Coloca esta migration no caminho de migrations de tenant do teu projecto
// (o mesmo sítio onde estão as migrations de alunos, turmas, etc.).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_emitidos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // 'declaracao' | 'certificado' | 'pauta' — usado para agrupar no gráfico
            $table->string('tipo');

            // Detalhe dentro do tipo: 'com_notas' | 'sem_notas' (declaração), etc. Opcional.
            $table->string('subtipo')->nullable();

            // Ajusta o tipo/coluna se aluno_id ou turma_id não forem uuid no teu schema.
            $table->uuid('aluno_id')->nullable();
            $table->uuid('turma_id')->nullable();

            $table->unsignedBigInteger('gerado_por_id')->nullable();

            $table->timestamps();

            $table->index('tipo');
            $table->index(['aluno_id', 'turma_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_emitidos');
    }
};