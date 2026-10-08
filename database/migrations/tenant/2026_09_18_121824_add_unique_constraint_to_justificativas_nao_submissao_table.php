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
        if (! Schema::hasTable('justificativas_nao_submissao')) {
            return;
        }

        $indices = DB::select('SHOW INDEX FROM justificativas_nao_submissao');
        $hasUnique = collect($indices)->contains(fn ($index) => $index->Key_name === 'justificativas_prazo_professor_unique');

        if ($hasUnique) {
            return;
        }

        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->unique(
                ['prazo_prova_id', 'professor_id'],
                'justificativas_prazo_professor_unique'
            );

            $table->dropIndex('justificativas_nao_submissao_prazo_prova_id_professor_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->index(['prazo_prova_id', 'professor_id']);
            $table->dropUnique('justificativas_prazo_professor_unique');
        });
    }
};
