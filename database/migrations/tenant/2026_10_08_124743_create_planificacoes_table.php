<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────────────────────────────────
        // Tabela principal — uma planificação por
        // (ano letivo + disciplina + classe + período)
        // ───────────────────────────────────────────────────
        Schema::create('planificacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('instituicao_id');
            $table->uuid('ano_letivo_id');      // FK central
            $table->uuid('disciplina_id');      // FK central
            $table->uuid('classe_id');          // FK tenant
            $table->string('periodo', 50);      // ex: '1º Trimestre'

            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->unsignedInteger('versao_atual')->default(0);

            $table->uuid('created_by');
            $table->timestamps();

            // Uma planificação por combinação exata
            $table->unique(
                ['ano_letivo_id', 'disciplina_id', 'classe_id', 'periodo'],
                'planificacoes_chave_unica'
            );

            $table->index('instituicao_id');
            $table->index(['disciplina_id', 'classe_id']);
            $table->index('ano_letivo_id');
        });

        // ───────────────────────────────────────────────────
        // Versões — cada upload cria uma nova linha
        // ───────────────────────────────────────────────────
        Schema::create('planificacao_versoes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('planificacao_id');
            $table->foreign('planificacao_id')
                ->references('id')
                ->on('planificacoes')
                ->cascadeOnDelete();

            $table->unsignedInteger('versao');

            $table->string('caminho_ficheiro');
            $table->string('nome_original');
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('mime_type', 100);

            $table->uuid('uploaded_by');
            $table->timestamps();

            $table->unique(['planificacao_id', 'versao'], 'planificacao_versao_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planificacao_versoes');
        Schema::dropIfExists('planificacoes');
    }
};