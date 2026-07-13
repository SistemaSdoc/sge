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
        Schema::create('ano_lectivos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome'); // ex: "2025/2026"
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->boolean('activo')->default(false); // só um ano lectivo activo de cada vez
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
        Schema::dropIfExists('ano_lectivos');
    }
};
