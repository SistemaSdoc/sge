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
        Schema::create('propinas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('aluno_id')
                ->constrained('alunos')
                ->cascadeOnDelete();

            $table->foreignUuid('ano_lectivo_id')
                ->constrained('ano_lectivos')
                ->restrictOnDelete();

            $table->foreignUuid('item_pagavel_id')
                ->constrained('itens_pagaveis')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('mes')->default(0);

            $table->decimal('valor_devido', 12, 2);

            $table->date('data_vencimento');

            $table->enum('estado', [
                'pendente',
                'parcial',
                'pago',
                'atrasado',
                'isento',
            ])->default('pendente');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['aluno_id', 'item_pagavel_id', 'ano_lectivo_id', 'mes'],
                'propina_aluno_item_ano_mes_unique'
            );
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
