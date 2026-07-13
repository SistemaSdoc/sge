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
        Schema::create('propinas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('aluno_id');
            $table->foreign('aluno_id')->references('id')->on('alunos')->cascadeOnDelete();

            $table->uuid('ano_lectivo_id');
            $table->foreign('ano_lectivo_id')->references('id')->on('ano_lectivos')->cascadeOnDelete();

            $table->uuid('item_pagavel_id');
            $table->foreign('item_pagavel_id')->references('id')->on('item_pagaveis')->cascadeOnDelete();

            $table->unsignedTinyInteger('mes')->nullable(); // 1-12, só para mensalidades
            $table->decimal('valor_devido', 10, 2);
            $table->date('data_vencimento');
            $table->enum('estado', ['pendente', 'pago', 'parcial', 'atrasado', 'isento'])->default('pendente');

            $table->softDeletes();
            $table->timestamps();

            // evita duplicar a mesma propina (aluno + item + mês) no mesmo ano lectivo
            $table->unique(['aluno_id', 'ano_lectivo_id', 'item_pagavel_id', 'mes'], 'propina_unica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propinas');
    }
};
