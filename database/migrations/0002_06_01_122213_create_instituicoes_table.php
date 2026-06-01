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
            $table->timestamps();
        });

        Schema::create('cursos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->text('descricao')->nullable();
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
            $table->timestamps();
        });

        Schema::create('curso_classe', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_tutelado_id');
            $table->foreign('curso_tutelado_id')->references('id')->on('curso_tutelado');
            $table->uuid('classe_id');
            $table->foreign('classe_id')->references('id')->on('classes');
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
            $table->boolean('tem_professor')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instituicoes');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('instituicao_curso');
        Schema::dropIfExists('curso_tutelado');
        Schema::dropIfExists('curso_classe');
        Schema::dropIfExists('curso_classe_turno');
        Schema::dropIfExists('classe_turno_disciplina');
    }
};
