<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissoes_provas', function (Blueprint $table) {
             $table->uuid('id')->primary();// auto-increment para esta tabela (opcional, mas mantém)

            // Chaves estrangeiras (UUID)
            $table->uuid('prazo_prova_id');      // referencia prazos_provas(id)
            $table->uuid('professor_id');        // referencia professores(id)
            $table->uuid('disciplina_id');
            $table->uuid('classe_id')->nullable();

            // Ficheiros
            $table->string('caminho_prova');
            $table->string('caminho_chave');

            // Versão e comentários
            $table->unsignedSmallInteger('versao')->default(1);
            $table->text('comentario_professor')->nullable();

            // Estados
            $table->enum('estado', [
                'pendente', 'em_revisao', 'aprovado', 'rejeitado', 'substituido'
            ])->default('pendente');

            $table->text('parecer_diretor')->nullable();

            $table->timestamp('data_submissao')->useCurrent();
            $table->timestamps();

            // FOREIGN KEYS
            $table->foreign('prazo_prova_id')->references('id')->on('prazos_provas')->onDelete('cascade');
            $table->foreign('professor_id')->references('id')->on('professores')->onDelete('cascade');
            $table->foreign('disciplina_id')->references('id')->on('disciplinas')->onDelete('cascade');
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');

            // Índices
            $table->index(['prazo_prova_id', 'estado']);
            $table->index('professor_id');
            $table->index('disciplina_id');

            // Garantir que não haja duas versões iguais para o mesmo professor/prazo
            $table->unique(['prazo_prova_id', 'professor_id', 'versao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissoes_provas');
    }
};