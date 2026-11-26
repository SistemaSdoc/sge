<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_prova_entradas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('calendario_prova_id');
            $table->foreign('calendario_prova_id')->references('id')->on('calendario_provas')->cascadeOnDelete();
            $table->uuid('disciplina_id');
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();

            $table->index(['calendario_prova_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_prova_entradas');
    }
};
