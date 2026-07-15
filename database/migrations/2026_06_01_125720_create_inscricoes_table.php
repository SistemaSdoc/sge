<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_classe_turno_id');
            $table->foreign('curso_classe_turno_id')->references('id')->on('curso_classe_turno');
            $table->uuid('candidato_id');
            $table->foreign('candidato_id')->references('id')->on('candidatos');
            $table->enum('status', ['pendente', 'apto_prova', 'aprovado', 'reprovado', 'reprovado_prova'])->default('pendente');
            $table->string('nota_teste')->nullable();
            $table->uuid('ano_lectivo_id');
            $table->foreign('ano_lectivo_id')->references('id')->on('ano_lectivos');
            $table->timestamps();
        });

        Schema::create('alunos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inscricao_id');
            $table->foreign('inscricao_id')->references('id')->on('inscricoes');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('matricula')->unique()->nullable();
            $table->enum('situacao', ['activo', 'finalista', 'concluido', 'reprovado', 'desistente'])->default('activo');
            $table->timestamps();
        });

        Schema::create('turma_aluno', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('turma_id');
            $table->foreign('turma_id')->references('id')->on('turmas');
            $table->uuid('aluno_id');
            $table->foreign('aluno_id')->references('id')->on('alunos');
            $table->boolean('activo')->default(true);
            $table->enum('situacao', ['activo', 'recurso', 'pap_concluido', 'concluido'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
        Schema::dropIfExists('alunos');
        Schema::dropIfExists('turma_aluno');

    }
};
