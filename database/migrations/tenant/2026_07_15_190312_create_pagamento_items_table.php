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
        Schema::create('pagamento_itens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pagamento_id')->constrained('pagamentos')->cascadeOnDelete();
            $table->foreignUuid('item_pagavel_id')->constrained('itens_pagaveis')->restrictOnDelete();
            $table->foreignUuid('aluno_id')->constrained('alunos'); // denormalizado — explico abaixo

            // mes = 0 quando não aplicável (itens anuais/únicos). Nunca null — ver nota sobre unique constraint.
            $table->unsignedTinyInteger('mes')->default(0);
            $table->unsignedSmallInteger('ano');

            $table->decimal('valor', 10, 2); // valor efectivamente pago nesta linha (pode ter desconto vs catálogo)
            $table->timestamps();

            $table->unique(['aluno_id', 'item_pagavel_id', 'ano', 'mes'], 'pagamento_itens_periodo_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamento_itens');
    }
};
