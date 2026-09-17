<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prazos_provas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // chave primária UUID

            $table->uuid('disciplina_id')->nullable();
            $table->uuid('classe_id')->nullable();    // em vez de turma_id
            $table->uuid('criado_por'); // diretor (users)

            // Dados do prazo
            $table->enum('tipo_prova', ['teste', 'exame', 'ficha', 'recuperacao'])->default('teste');
            $table->string('titulo', 255)->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamp('data_inicio');
            $table->timestamp('data_limite');

            $table->string('ano_letivo', 9);
            $table->string('periodo', 20);

            $table->enum('status', ['aberto', 'fechado', 'expirado'])->default('aberto');
            $table->boolean('permite_reenvio')->default(true);

            $table->timestamps();

            // Foreign keys
            $table->foreign('disciplina_id')->references('id')->on('disciplinas')->onDelete('set null');
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('criado_por')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index(['disciplina_id', 'classe_id']);
            $table->index('status');
            $table->index('data_limite');
            $table->index('ano_letivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prazos_provas');
    }
};