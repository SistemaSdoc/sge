<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Verifica se um índice existe na tabela.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::connection('tenant')->select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return !empty($result);
    }

    /**
     * Verifica se uma foreign key existe na tabela.
     */
    private function foreignExists(string $table, string $fkName): bool
    {
        $result = DB::connection('tenant')->select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $fkName]
        );
        return !empty($result);
    }

    public function up(): void
    {
        $table = 'justificativas_nao_submissao';

        // ────────────────────────────────────────────────────────
        // 1. Remover foreign keys (para libertar índices)
        // ────────────────────────────────────────────────────────
        $fksToDrop = [
            "{$table}_prazo_prova_id_foreign",
            "{$table}_professor_id_foreign",
            "justificativas_nao_submissao_prazo_prova_id_foreign",
            "justificativas_nao_submissao_professor_id_foreign",
        ];

        foreach ($fksToDrop as $fk) {
            if ($this->foreignExists($table, $fk)) {
                Schema::table($table, function (Blueprint $t) use ($fk) {
                    $t->dropForeign($fk);
                });
            }
        }

        // ────────────────────────────────────────────────────────
        // 2. Remover índices temporários (se existirem)
        // ────────────────────────────────────────────────────────
        foreach (['tmp_prazo_idx', 'tmp_professor_idx'] as $idx) {
            if ($this->indexExists($table, $idx)) {
                Schema::table($table, function (Blueprint $t) use ($idx) {
                    $t->dropIndex($idx);
                });
            }
        }

        // ────────────────────────────────────────────────────────
        // 3. Remover unique antigo (se existir)
        // ────────────────────────────────────────────────────────
        if ($this->indexExists($table, 'justificativas_prazo_professor_unique')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique('justificativas_prazo_professor_unique');
            });
        }

        // ────────────────────────────────────────────────────────
        // 4. Criar novo unique com turma_id (se não existir)
        // ────────────────────────────────────────────────────────
        if (!$this->indexExists($table, 'justificativas_prazo_professor_turma_unique')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unique(
                    ['prazo_prova_id', 'professor_id', 'turma_id'],
                    'justificativas_prazo_professor_turma_unique'
                );
            });
        }

        // ────────────────────────────────────────────────────────
        // 5. Recriar foreign keys (se não existirem)
        // ────────────────────────────────────────────────────────
        $fksToCreate = [
            [
                'name'       => "{$table}_prazo_prova_id_foreign",
                'column'     => 'prazo_prova_id',
                'ref_table'  => 'prazos_provas',
                'ref_column' => 'id',
            ],
            [
                'name'       => "{$table}_professor_id_foreign",
                'column'     => 'professor_id',
                'ref_table'  => 'professores',
                'ref_column' => 'id',
            ],
        ];

        foreach ($fksToCreate as $fk) {
            if (!$this->foreignExists($table, $fk['name'])) {
                Schema::table($table, function (Blueprint $t) use ($fk) {
                    $t->foreign($fk['column'], $fk['name'])
                        ->references($fk['ref_column'])
                        ->on($fk['ref_table'])
                        ->onDelete('cascade');
                });
            }
        }
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