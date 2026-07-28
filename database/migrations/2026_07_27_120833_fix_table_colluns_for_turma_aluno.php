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
        Schema::table('turma_aluno', function (Blueprint $table) {
            $table->renameColumn('situacao', 'estado_matricula');
            $table->renameColumn('resultado', 'resultado_academico');
        });

        Schema::table('turma_aluno', function (Blueprint $table) {
            $table->enum('estado_matricula', [
                'activo',
                'aguarda_recurso',
                'transitado',
                'retido',
                'concluido',
            ])->default('activo')->change();

            $table->enum('resultado_academico', [
                'aprovado',
                'aprovado_com_recurso',
                'reprovado',
                'reprovado_recurso',
                'eef',
                'incompleto',
                'sem_notas',
                'concluido',
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turma_aluno', function (Blueprint $table) {
            $table->renameColumn('estado_matricula', 'situacao');
            $table->renameColumn('resultado_academico', 'resultado');
        });

        Schema::table('turma_aluno', function (Blueprint $table) {
            $table->enum('situacao', [
                'activo',
                'recurso',
                'pap_concluido',
                'concluido',
            ])->default('activo')->change();

            $table->string('resultado')->nullable()->change();
        });
    }
};
