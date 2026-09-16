<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKeys = [
            'classe_turno_disciplina' => ['ano_lectivo_id'],
            'turmas' => ['ano_lectivo_id'],
            'inscricoes' => ['ano_lectivo_id'],
            'regras_avaliacao' => ['ano_lectivo_id'],
            'propinas' => ['ano_lectivo_id'],
            'periodo_lancamento_notas' => ['ano_lectivo_id'],
            'turma_aluno' => ['ano_lectivo_id'],
            'confirmacao_matricula' => [
                'ano_lectivo_atual_id',
                'ano_lectivo_proximo_id',
            ],
        ];

        foreach ($foreignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->dropForeign([$column]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ano_lectivos')) {
            $foreignKeys = [
                'classe_turno_disciplina' => ['ano_lectivo_id'],
                'turmas' => ['ano_lectivo_id'],
                'inscricoes' => ['ano_lectivo_id'],
                'regras_avaliacao' => ['ano_lectivo_id'],
                'propinas' => ['ano_lectivo_id'],
                'periodo_lancamento_notas' => ['ano_lectivo_id'],
                'turma_aluno' => ['ano_lectivo_id'],
                'confirmacao_matricula' => [
                    'ano_lectivo_atual_id',
                    'ano_lectivo_proximo_id',
                ],
            ];

            foreach ($foreignKeys as $tableName => $columns) {
                if (! Schema::hasTable($tableName)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                    foreach ($columns as $column) {
                        $table->foreign($column)
                            ->references('id')
                            ->on('ano_lectivos');
                    }
                });
            }
        }
    }
};
