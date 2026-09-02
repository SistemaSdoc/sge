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
        Schema::create('instituicoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('sigla')->nullable();
            $table->enum('tipo', ['instituto', 'colegio'])->default('instituto');
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('provincia')->nullable();
            $table->string('endereco')->nullable();
            $table->string('logo')->nullable();
            $table->integer('status')->default(1);
            $table->text('descricao')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ano_lectivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome'); // ex: "2025/2026"
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('activo')->default(false); // só um ano lectivo activo de cada vez
            $table->enum('estado', ['planeado', 'matriculas_abertas', 'em_curso', 'encerrado'])->default('planeado');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cursos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->integer('duracao_anos');
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('instituicao_curso', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_id');
            $table->foreign('curso_id')->references('id')->on('cursos');
            $table->uuid('instituicao_id');
            $table->foreign('instituicao_id')->references('id')->on('instituicoes');
            $table->integer('duracao_anos');
            $table->timestamps();
        });

        Schema::create('curso_tutelado', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instituicao_curso_id');
            $table->foreign('instituicao_curso_id')->references('id')->on('instituicao_curso');
            $table->uuid('instituicao_tutora_id');
            $table->foreign('instituicao_tutora_id')->references('id')->on('instituicoes');
            $table->string('criterios_pap_path')->nullable();
            $table->string('manual_pt_path')->nullable();
            $table->string('estrutura_trabalho_pap_path')->nullable();
            $table->timestamps();
        });

        Schema::create('curso_classe', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_tutelado_id');
            $table->foreign('curso_tutelado_id')->references('id')->on('curso_tutelado');
            $table->uuid('classe_id');
            $table->foreign('classe_id')->references('id')->on('classes');
            $table->uuid('nivel_ensino_id');
            $table->foreign('nivel_ensino_id')->references('id')->on('niveis_ensino');
            $table->timestamps();
        });

        Schema::create('curso_classe_turno', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('turno_id');
            $table->foreign('turno_id')->references('id')->on('turnos');
            $table->uuid('curso_classe_id');
            $table->foreign('curso_classe_id')->references('id')->on('curso_classe');
            $table->timestamps();
        });

        Schema::create('classe_turno_disciplina', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_classe_turno_id');
            $table->foreign('curso_classe_turno_id')->references('id')->on('curso_classe_turno');
            $table->uuid('disciplina_id');
            $table->foreign('disciplina_id')->references('id')->on('disciplinas');
            $table->string('carga_horaria')->nullable();
            $table->uuid('ano_lectivo_id');
            $table->foreign('ano_lectivo_id')->references('id')->on('ano_lectivos');
            $table->boolean('tem_professor')->default(false)->nullable();
            $table->timestamps();

            $table->unique(['curso_classe_turno_id', 'disciplina_id', 'ano_lectivo_id'], 'cct_disc_al_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_turno_disciplina');
        Schema::dropIfExists('curso_classe_turno');
        Schema::dropIfExists('curso_classe');
        Schema::dropIfExists('curso_tutelado');
        Schema::dropIfExists('instituicao_curso');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('instituicoes');
        Schema::dropIfExists('ano_lectivos');
    }
};
