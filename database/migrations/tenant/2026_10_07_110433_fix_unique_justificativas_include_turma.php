<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            // 1. Índice temporário para as FKs não ficarem sem suporte
            $table->index('prazo_prova_id', 'tmp_prazo_idx');
            $table->index('professor_id', 'tmp_professor_idx');
        });

        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            // 2. Agora é seguro dropar o unique antigo
            $table->dropUnique('justificativas_prazo_professor_unique');

            // 3. Criar o novo unique com turma_id
            $table->unique(
                ['prazo_prova_id', 'professor_id', 'turma_id'],
                'justificativas_prazo_professor_turma_unique'
            );
        });

        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            // 4. Limpar os índices temporários
            $table->dropIndex('tmp_prazo_idx');
            $table->dropIndex('tmp_professor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->index('prazo_prova_id', 'tmp_prazo_idx');
            $table->index('professor_id', 'tmp_professor_idx');
        });

        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->dropUnique('justificativas_prazo_professor_turma_unique');
            $table->unique(
                ['prazo_prova_id', 'professor_id'],
                'justificativas_prazo_professor_unique'
            );
        });

        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->dropIndex('tmp_prazo_idx');
            $table->dropIndex('tmp_professor_idx');
        });
    }
};