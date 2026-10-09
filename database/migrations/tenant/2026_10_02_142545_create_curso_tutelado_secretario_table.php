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
        if (Schema::hasTable('curso_tutelado_secretario')) {
            return;
        }

        Schema::create('curso_tutelado_secretario', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curso_tutelado_id')->constrained('curso_tutelado')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['curso_tutelado_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curso_tutelado_secretario');
    }
};
