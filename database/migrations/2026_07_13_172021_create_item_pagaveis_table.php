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
        Schema::create('itens_pagaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instituicao_id')->constrained('instituicoes'); 
            $table->foreignUuid('curso_classe_id')->nullable()->constrained('curso_classe')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('valor', 10, 2);
            $table->enum('frequencia', ['mensal', 'anual', 'unico']);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
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
