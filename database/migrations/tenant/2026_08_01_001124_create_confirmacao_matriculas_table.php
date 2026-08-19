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
        Schema::create('confirmacao_matricula', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Aluno que está confirmando matrícula
            $table->uuid('aluno_id');
            $table->foreign('aluno_id')->references('id')->on('alunos')->onDelete('cascade');

            // Ano lectivo atual (de onde vem)
            $table->uuid('ano_lectivo_atual_id');
            $table->foreign('ano_lectivo_atual_id')->references('id')->on('ano_lectivos')->onDelete('cascade');

            // Ano lectivo próximo (para onde vai)
            $table->uuid('ano_lectivo_proximo_id');
            $table->foreign('ano_lectivo_proximo_id')->references('id')->on('ano_lectivos')->onDelete('cascade');

            // Turma atual (de onde sai)
            $table->uuid('turma_atual_id');
            $table->foreign('turma_atual_id')->references('id')->on('turmas')->onDelete('cascade');

            // Turma nova (para onde vai)
            $table->uuid('turma_nova_id');
            $table->foreign('turma_nova_id')->references('id')->on('turmas')->onDelete('cascade');

            // Status da confirmação
            $table->enum('status', ['confirmada', 'nao_compareceu', 'cancelada'])->default('confirmada');

            // Data de confirmação
            $table->timestamp('data_confirmacao');

            // Quem confirmou (secretaria/admin)
            $table->uuid('confirmado_por');
            $table->foreign('confirmado_por')->references('id')->on('users')->onDelete('restrict');

            // Observações
            $table->text('observacoes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmacao_matricula');
    }
};
