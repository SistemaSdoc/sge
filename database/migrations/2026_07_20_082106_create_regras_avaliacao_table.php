<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_avaliacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('nivel_ensino')->nullable(); // Ex: 'secundario', 'superior', etc.
            $table->decimal('media_minima_aprovacao', 5, 2)->default(10.00);
            $table->decimal('frequencia_minima', 5, 2)->default(75.00);
            $table->unsignedInteger('max_disciplinas_negativas')->nullable();
            $table->boolean('permite_recurso')->default(true);
            $table->boolean('activo')->default(true);
            $table->foreignUuid('nivel_ensino_id')->nullable()->constrained('niveis_ensino')->nullOnDelete();
            $table->foreignUuid('instituicao_id')->constrained('instituicoes')->cascadeOnDelete();
            $table->foreignUuid('ano_lectivo_id')->constrained('ano_lectivos')->cascadeOnDelete();
            $table->foreignUuid('classe_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->timestamps();

            // Unique: uma regra por instituição + ano + nível de ensino (mas pode ter várias se nível de ensino for null)
            $table->unique(['instituicao_id', 'ano_lectivo_id', 'nivel_ensino_id', 'classe_id'], 'regras_avaliacao_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_avaliacao');
    }
};
