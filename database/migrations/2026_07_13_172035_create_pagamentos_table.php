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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('propina_id');
            $table->foreign('propina_id')->references('id')->on('propinas')->cascadeOnDelete();

            $table->decimal('valor_pago', 10, 2);
            $table->date('data_pagamento');
            $table->enum('metodo', ['dinheiro', 'transferencia', 'multicaixa', 'outro'])->default('dinheiro');
            $table->string('comprovativo_path')->nullable();

            $table->uuid('registado_por');
            $table->foreign('registado_por')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
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
