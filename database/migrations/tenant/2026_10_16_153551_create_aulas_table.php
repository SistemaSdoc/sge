<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aulas', function (Blueprint $table) {
            $table->char('id', 36)->primary();

            // ── Elo com o horário (opcional: aulas extra/reposição não têm slot) ──
            $table->char('classe_turno_disciplina_horario_id', 36)->nullable();

            // ── Desnormalizados para queries rápidas ──
            $table->char('turma_id', 36);
            $table->char('disciplina_id', 36);
            $table->char('professor_id', 36);

            // ── Substituição (professor original cedeu a aula) ──
            $table->char('professor_original_id', 36)->nullable();

            // ── Quando ──
            $table->date('data');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();

            // ── Tipo ──
            $table->string('tipo', 20)->default('normal'); // normal|substituicao|extra|reposicao

            // ── Conteúdo ──
            $table->string('titulo', 200)->nullable();
            $table->text('conteudo_previsto')->nullable(); // rascunho antes da aula
            $table->text('sumario')->nullable();            // o que foi dado
            $table->text('tpc')->nullable();
            $table->text('observacoes')->nullable();        // internas (não ao aluno)

            // ── Estado ──
            $table->string('estado', 20)->default('rascunho'); // rascunho|publicado|cancelado
            $table->timestamp('publicado_em')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Índices ──
            $table->index(['turma_id', 'data']);
            $table->index(['professor_id', 'data']);
            $table->index(['disciplina_id', 'data']);
            $table->index('estado');
            $table->index('data');

            // ── Um slot do horário só pode ter uma aula por data ──
            // (MySQL permite múltiplos NULL neste UNIQUE → aulas extra não colidem)
            $table->unique(
                ['classe_turno_disciplina_horario_id', 'data'],
                'aulas_slot_data_unique'
            );

            // ── FKs ──
            $table->foreign('classe_turno_disciplina_horario_id')
                ->references('id')->on('classe_turno_disciplina_horarios')
                ->onDelete('set null');

            $table->foreign('turma_id')->references('id')->on('turmas')->onDelete('cascade');
            $table->foreign('professor_id')->references('id')->on('professores')->onDelete('cascade');
            $table->foreign('professor_original_id')->references('id')->on('professores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};