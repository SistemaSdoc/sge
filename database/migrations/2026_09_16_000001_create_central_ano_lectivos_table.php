<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ano_lectivos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('ano_inicio');
            $table->string('nome');
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('activo')->default(false);
            $table->enum('estado', ['planeado', 'matriculas_abertas', 'em_curso', 'encerrado'])->default('planeado');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['ano_inicio', 'deleted_at'], 'ano_lectivos_ano_inicio_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ano_lectivos');
    }
};
