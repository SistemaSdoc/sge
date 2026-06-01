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
        Schema::create('avisos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['aviso', 'evento', 'urgente'])->default('aviso');
            $table->dateTime('data')->nullable();
            $table->boolean('ativo')->default(true);
            $table->uuid('instituicao_id');
            $table->foreign('instituicao_id')->references('id')->on('instituicoes')->onDelete('cascade');
            $table->enum('destinatario', ['todos', 'alunos', 'professores'])->default('todos');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
