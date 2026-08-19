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

        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('nivel_ensino');
            $table->boolean('emite_certificado')->default(false);
            $table->string('tipo_certificado')->nullable();
            $table->integer('ordem')->nullable();
            $table->timestamps();
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('disciplinas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('sigla')->nullable();
            $table->enum('componente', ['sociocultural', 'cientifica', 'tecnica'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('disciplinas');
    }
};
