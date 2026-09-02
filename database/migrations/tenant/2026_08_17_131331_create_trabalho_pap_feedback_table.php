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
        Schema::create('trabalho_pap_feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('trabalho_pap_id')
                ->constrained('trabalho_pap')
                ->cascadeOnDelete();

            $table->foreignUuid('versao_id')
                ->constrained('trabalho_pap_versoes')
                ->cascadeOnDelete();

            $table->foreignUuid('utilizador_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('tipo', [
                'correcao_tutor',
                'aprovacao_tutor',
                'correcao_coordenacao',
                'aprovacao_coordenacao',
            ]);

            $table->text('comentario')->nullable();
            $table->string('caminho_ficheiro_correcao')->nullable();
            $table->string('nome_original_correcao')->nullable();

            $table->string('estado_anterior');
            $table->string('estado_novo');

            $table->timestamps();

            $table->index(['trabalho_pap_id', 'versao_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabalho_pap_feedbacks');
    }
};
