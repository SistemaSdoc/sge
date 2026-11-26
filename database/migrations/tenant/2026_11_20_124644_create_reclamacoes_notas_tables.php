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
        Schema::create('reclamacoes_notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nota_id')->constrained('notas')->restrictOnDelete();
            $table->foreignUuid('solicitado_por')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('decidido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('componente', [
                'mac',
                'nota_prova_professor',
                'nota_prova_trimestral',
                'nota_recurso',
            ]);
            $table->decimal('nota_original', 5, 2);
            $table->decimal('nota_reclamada', 5, 2)->nullable();
            $table->decimal('nota_corrigida', 5, 2)->nullable();
            $table->text('justificativa');
            $table->enum('status', [
                'pendente',
                'em_analise',
                'aguarda_informacao',
                'aprovada',
                'reprovada',
            ])->default('pendente');
            $table->text('parecer')->nullable();
            $table->timestamp('decidido_em')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['nota_id', 'componente', 'status']);
        });

        Schema::create('anexos_reclamacoes_notas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reclamacao_nota_id')
                ->constrained('reclamacoes_notas')
                ->cascadeOnDelete();
            $table->string('caminho');
            $table->string('nome_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anexos_reclamacoes_notas');
        Schema::dropIfExists('reclamacoes_notas');
    }
};
