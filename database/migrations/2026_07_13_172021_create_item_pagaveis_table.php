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
        Schema::create('item_pagaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome'); // ex: "Propina Mensal", "Matrícula"
            $table->enum('tipo', ['mensalidade', 'matricula', 'taxa', 'outro'])->default('mensalidade');
            $table->decimal('valor_padrao', 10, 2);

            $table->uuid('instituicao_id');
            $table->foreign('instituicao_id')->references('id')->on('instituicoes')->cascadeOnDelete();

            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pagaveis');
    }
};
