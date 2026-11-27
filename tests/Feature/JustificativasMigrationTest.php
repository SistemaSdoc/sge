<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

test('it resumes the partially applied tenant migration without duplicate indexes', function () {
    Schema::create('justificativas_nao_submissao', function (Blueprint $table): void {
        $table->string('prazo_prova_id');
        $table->string('professor_id');
        $table->string('turma_id')->nullable();
        $table->unique(
            ['prazo_prova_id', 'professor_id'],
            'justificativas_prazo_professor_unique'
        );
    });

    Schema::table('justificativas_nao_submissao', function (Blueprint $table): void {
        $table->index('prazo_prova_id', 'tmp_prazo_idx');
        $table->index('professor_id', 'tmp_professor_idx');
        $table->dropUnique('justificativas_prazo_professor_unique');
        $table->unique(
            ['prazo_prova_id', 'professor_id', 'turma_id'],
            'justificativas_prazo_professor_turma_unique'
        );
    });

    $migration = require database_path('migrations/tenant/2026_10_07_110433_fix_unique_justificativas_include_turma.php');

    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('justificativas_nao_submissao', 'justificativas_prazo_professor_turma_unique', 'unique'))
        ->toBeTrue()
        ->and(Schema::hasIndex('justificativas_nao_submissao', 'tmp_prazo_idx'))
        ->toBeFalse()
        ->and(Schema::hasIndex('justificativas_nao_submissao', 'tmp_professor_idx'))
        ->toBeFalse()
        ->and(Schema::hasIndex('justificativas_nao_submissao', 'justificativas_nao_submissao_professor_id_index'))
        ->toBeTrue();
});
