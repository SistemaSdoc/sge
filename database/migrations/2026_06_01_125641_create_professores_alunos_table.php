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
        Schema::create('professores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('especialidade')->nullable();
            $table->timestamps();
        });

        Schema::create('turmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->integer('max_alunos')->nullable();
            $table->uuid('curso_classe_turno_id');
            $table->foreign('curso_classe_turno_id')->references('id')->on('curso_classe_turno');
            $table->uuid('ano_lectivo_id');
            $table->foreign('ano_lectivo_id')->references('id')->on('ano_lectivos');
            $table->timestamps();

            $table->unique(['curso_classe_turno_id', 'ano_lectivo_id', 'nome'], 'unique_turma_ano');
        });

        Schema::create('turma_disciplina_professor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('classe_turno_disciplina_id');
            $table->foreign('classe_turno_disciplina_id')->references('id')->on('classe_turno_disciplina');
            $table->uuid('professor_id');
            $table->foreign('professor_id')->references('id')->on('professores');
            $table->uuid('turma_id');
            $table->foreign('turma_id')->references('id')->on('turmas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professores');
        Schema::dropIfExists('turmas');
        Schema::dropIfExists('turma_disciplina_professor');
    }
};
