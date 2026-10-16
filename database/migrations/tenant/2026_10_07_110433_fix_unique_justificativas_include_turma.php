<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
public function up(): void
{
    $tabela = 'justificativas_nao_submissao';

    // índices temporários para as FKs não perderem suporte durante o drop do unique antigo
    Schema::table($tabela, function (Blueprint $table) use ($tabela) {
        if (! Schema::hasIndex($tabela, 'tmp_prazo_idx')) {
            $table->index('prazo_prova_id', 'tmp_prazo_idx');
        }
        if (! Schema::hasIndex($tabela, 'justificativas_professor_idx')
            && ! Schema::hasIndex($tabela, 'tmp_professor_idx')) {
            $table->index('professor_id', 'justificativas_professor_idx');
        }
    });

    Schema::table($tabela, function (Blueprint $table) use ($tabela) {
        if (Schema::hasIndex($tabela, 'justificativas_prazo_professor_unique')) {
            $table->dropUnique('justificativas_prazo_professor_unique');
        }
        if (! Schema::hasIndex($tabela, 'justificativas_prazo_professor_turma_unique')) {
            $table->unique(
                ['prazo_prova_id', 'professor_id', 'turma_id'],
                'justificativas_prazo_professor_turma_unique'
            );
        }
    });

    // só o tmp_prazo_idx é redundante (o novo unique cobre prazo_prova_id)
    Schema::table($tabela, function (Blueprint $table) use ($tabela) {
        if (Schema::hasIndex($tabela, 'tmp_prazo_idx')) {
            $table->dropIndex('tmp_prazo_idx');
        }
    });
}

    public function down(): void
    {
        $table = 'justificativas_nao_submissao';

        // Remover FKs
        foreach (["{$table}_prazo_prova_id_foreign", "{$table}_professor_id_foreign"] as $fk) {
            if ($this->foreignExists($table, $fk)) {
                Schema::table($table, function (Blueprint $t) use ($fk) {
                    $t->dropForeign($fk);
                });
            }
        }

        // Remover unique novo
        if ($this->indexExists($table, 'justificativas_prazo_professor_turma_unique')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique('justificativas_prazo_professor_turma_unique');
            });
        }

        // Recriar unique antigo
        if (!$this->indexExists($table, 'justificativas_prazo_professor_unique')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unique(
                    ['prazo_prova_id', 'professor_id'],
                    'justificativas_prazo_professor_unique'
                );
            });
        }

        // Recriar FKs
        $fksToCreate = [
            ['name' => "{$table}_prazo_prova_id_foreign", 'column' => 'prazo_prova_id', 'ref_table' => 'prazos_provas'],
            ['name' => "{$table}_professor_id_foreign",    'column' => 'professor_id',    'ref_table' => 'professores'],
        ];

        foreach ($fksToCreate as $fk) {
            if (!$this->foreignExists($table, $fk['name'])) {
                Schema::table($table, function (Blueprint $t) use ($fk) {
                    $t->foreign($fk['column'], $fk['name'])
                        ->references('id')
                        ->on($fk['ref_table'])
                        ->onDelete('cascade');
                });
            }
        }
    }
};
