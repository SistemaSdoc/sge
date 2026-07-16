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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('aluno_id')->constrained('alunos');
            $table->foreignUuid('instituicao_id')->constrained('instituicoes');
            $table->foreignUuid('registado_por')->constrained('users'); // quem processou
            $table->date('data_pagamento');
            $table->decimal('valor_total', 12, 2); // soma dos pagamento_itens, denormalizado p/ listagens rápidas
            $table->enum('metodo', ['dinheiro', 'transferencia', 'multicaixa', 'outro']);
            $table->string('referencia')->nullable(); // nº de recibo/transacção
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes(); // recibo emitido não pode desaparecer de vez
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
