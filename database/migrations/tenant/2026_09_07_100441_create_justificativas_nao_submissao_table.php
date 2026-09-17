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
        Schema::create('justificativas_nao_submissao', function (Blueprint $table) {
            // Chave primária UUID (se seus modelos usarem UUID)
            $table->uuid('id')->primary();

            // Chaves estrangeiras
            $table->uuid('prazo_prova_id');
            $table->uuid('professor_id');

            // Campos principais
            $table->text('motivo');
            $table->timestamp('data_justificativa')->useCurrent();

            // Status da justificativa
            $table->enum('status', ['pendente', 'aceita', 'recusada'])->default('pendente');

            // Avaliação (opcional)
            $table->uuid('avaliado_por')->nullable();
            $table->timestamp('data_avaliacao')->nullable();

            // Timestamps (created_at, updated_at)
            $table->timestamps();

            // Foreign keys
            $table->foreign('prazo_prova_id')
                  ->references('id')
                  ->on('prazos_provas')
                  ->onDelete('cascade');

            $table->foreign('professor_id')
                  ->references('id')
                  ->on('professores')
                  ->onDelete('cascade');

            $table->foreign('avaliado_por')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Índices para consultas rápidas
            $table->index(['prazo_prova_id', 'professor_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificativas_nao_submissao');
    }
};