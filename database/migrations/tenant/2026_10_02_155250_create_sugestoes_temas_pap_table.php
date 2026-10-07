<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sugestoes_temas_pap', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('curso_tutelado_id')
                ->constrained('curso_tutelado')
                ->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['curso_tutelado_id', 'titulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sugestoes_temas_pap');
    }
};
