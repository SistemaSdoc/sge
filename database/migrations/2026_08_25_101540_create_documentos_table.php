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
        Schema::create('documentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_pagavel_id')->constrained('itens_pagaveis')->cascadeOnDelete();
            $table->foreignUuid('instituicao_id')->constrained('instituicoes');
            $table->enum('subtipo', [
                'declaracao_sem_notas',
                'declaracao_com_notas',
                'certificado',
            ]);
            $table->string('template_path')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
