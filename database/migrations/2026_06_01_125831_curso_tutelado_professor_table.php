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
        Schema::create('curso_tutelado_professor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('curso_tutelado_id');
            $table->foreign('curso_tutelado_id')->references('id')->on('curso_tutelado')->onDelete('cascade');
            $table->uuid('professor_id');
            $table->foreign('professor_id')->references('id')->on('professores')->onDelete('cascade');
            $table->enum('tipo', ['principal', 'colaborador'])->default('colaborador');
            $table->boolean('coordenador')->default(false);
            $table->unique(['curso_tutelado_id', 'professor_id']);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curso_tutelado_professor');

    }
};
