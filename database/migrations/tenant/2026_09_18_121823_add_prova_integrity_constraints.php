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
        Schema::table('submissoes_provas', function (Blueprint $table): void {
            $table->dropUnique('submissoes_provas_prazo_prova_id_professor_id_versao_unique');
            $table->unique(
                ['prazo_prova_id', 'professor_id', 'turma_id', 'versao'],
                'submissoes_provas_prazo_professor_turma_versao_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissoes_provas', function (Blueprint $table): void {
            $table->dropUnique('submissoes_provas_prazo_professor_turma_versao_unique');
            $table->unique(
                ['prazo_prova_id', 'professor_id', 'versao'],
                'submissoes_provas_prazo_prova_id_professor_id_versao_unique'
            );
        });
    }
};
