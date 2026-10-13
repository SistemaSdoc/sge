<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'justificativas_nao_submissao';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasIndex($tableName, 'tmp_prazo_idx')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('prazo_prova_id', 'tmp_prazo_idx');
            });
        }

        if (! Schema::hasIndex($tableName, 'justificativas_nao_submissao_professor_id_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('professor_id');
            });
        }

        if (Schema::hasIndex($tableName, 'justificativas_prazo_professor_unique', 'unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique('justificativas_prazo_professor_unique');
            });
        }

        if (! Schema::hasIndex($tableName, 'justificativas_prazo_professor_turma_unique', 'unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(
                    ['prazo_prova_id', 'professor_id', 'turma_id'],
                    'justificativas_prazo_professor_turma_unique'
                );
            });
        }

        if (Schema::hasIndex($tableName, 'tmp_prazo_idx')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('tmp_prazo_idx');
            });
        }

        if (Schema::hasIndex($tableName, 'tmp_professor_idx')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('tmp_professor_idx');
            });
        }
    }

    public function down(): void
    {
        $tableName = 'justificativas_nao_submissao';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (Schema::hasIndex($tableName, 'justificativas_prazo_professor_turma_unique', 'unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique('justificativas_prazo_professor_turma_unique');
            });
        }

        if (! Schema::hasIndex($tableName, 'justificativas_prazo_professor_unique', 'unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(
                    ['prazo_prova_id', 'professor_id'],
                    'justificativas_prazo_professor_unique'
                );
            });
        }

        if (Schema::hasIndex($tableName, 'tmp_prazo_idx')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('tmp_prazo_idx');
            });
        }
    }
};
