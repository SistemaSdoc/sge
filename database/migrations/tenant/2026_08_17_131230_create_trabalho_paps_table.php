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
        Schema::create('trabalho_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('grupo_pap_id')
                ->unique() // 1:1 com grupo_pap
                ->constrained('grupo_pap')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pendente_entrega',
                'em_analise_tutor',
                'correcao_tutor',
                'em_analise_coordenacao',
                'correcao_coordenacao',
                'aprovado',
            ])->default('pendente_entrega');

            $table->foreignUuid('aprovado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('data_aprovacao')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabalho_pap');
    }
};
