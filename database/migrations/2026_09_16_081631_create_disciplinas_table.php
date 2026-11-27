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
        Schema::create('disciplinas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome')->unique();
            $table->string('sigla', 50)->nullable();
            $table->enum('componente', ['sociocultural', 'cientifica', 'tecnica'])->nullable();
            $table->unsignedSmallInteger('carga_horaria');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disciplinas');
    }
};
