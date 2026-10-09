<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('submissoes_provas')) {
            return;
        }

        $indices = DB::select('SHOW INDEX FROM submissoes_provas');
        $hasNewUnique = collect($indices)->contains(fn ($index) => $index->Key_name === 'submissoes_provas_prazo_professor_turma_versao_unique');

        if ($hasNewUnique) {
            return;
        }

        $hasOldUnique = collect($indices)->contains(fn ($index) => $index->Key_name === 'submissoes_provas_prazo_prova_id_professor_id_versao_unique');

        Schema::table('submissoes_provas', function (Blueprint $table) use ($hasOldUnique): void {
            if ($hasOldUnique) {
                $table->dropUnique('submissoes_provas_prazo_prova_id_professor_id_versao_unique');
            }

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
        if (! Schema::hasTable('submissoes_provas')) {
            return;
        }

        $indices = DB::select('SHOW INDEX FROM submissoes_provas');
        $hasNewUnique = collect($indices)->contains(fn ($index) => $index->Key_name === 'submissoes_provas_prazo_professor_turma_versao_unique');
        $hasOldUnique = collect($indices)->contains(fn ($index) => $index->Key_name === 'submissoes_provas_prazo_prova_id_professor_id_versao_unique');

        Schema::table('submissoes_provas', function (Blueprint $table) use ($hasNewUnique, $hasOldUnique): void {
            if ($hasNewUnique) {
                $table->dropUnique('submissoes_provas_prazo_professor_turma_versao_unique');
            }

            if (! $hasOldUnique) {
                $table->unique(
                    ['prazo_prova_id', 'professor_id', 'versao'],
                    'submissoes_provas_prazo_prova_id_professor_id_versao_unique'
                );
            }
        });
    }
};
