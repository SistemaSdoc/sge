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
        Schema::create('grupo_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('turma_id');
            $table->foreign('turma_id')->references('id')->on('turmas');
            $table->uuid('professor_tutor_id');
            $table->foreign('professor_tutor_id')->references('id')->on('professores');
            $table->string('nome_grupo');
            $table->string('tema_grupo')->nullable();
            $table->text('problema')->nullable();
            $table->text('objectivos')->nullable();

            // ← ADICIONA AQUI
            $table->enum('status_aprovacao', [
                'rascunho',
                'submetido',
                'pendente',
                'aprovado',
                'reprovado',
                'melhoria-solicitada'
            ])->default('rascunho');

            $table->uuid('aprovado_por_id')->nullable();
            $table->foreign('aprovado_por_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('data_aprovacao')->nullable();
            $table->text('comentario_aprovacao')->nullable();
            // ← FIM DOS NOVOS CAMPOS
            $table->text('estudo_caso')->nullable();
            $table->string('trabalho_grupo')->nullable();
            $table->enum('status', ['pendente', 'em-andamento', 'concluido'])->default('pendente');
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->dateTime('data_defesa')->nullable();
            $table->string('local_defesa')->nullable();
            $table->timestamps();

            $table->index('status_aprovacao');
        });

        Schema::create('elementos_grupo_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grupo_pap_id');
            $table->foreign('grupo_pap_id')->references('id')->on('grupo_pap');
            $table->uuid('aluno_id');
            $table->foreign('aluno_id')->references('id')->on('alunos');
            $table->decimal('nota_individual', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('banca_juri_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('professor_id');
            $table->foreign('professor_id')->references('id')->on('professores');
            $table->uuid('grupo_pap_id');
            $table->foreign('grupo_pap_id')->references('id')->on('grupo_pap');
            $table->string('funcao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_pap');
        Schema::dropIfExists('elementos_grupo_pap');
        Schema::dropIfExists('banca_juri_pap');

    }
};
