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
        Schema::create('trabalho_pap_versoes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('trabalho_pap_id')
                ->constrained('trabalho_pap')
                ->cascadeOnDelete();

            $table->foreignUuid('submetido_por_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('numero_versao'); // 1, 2, 3...
            $table->string('caminho_ficheiro');            // path do PDF no storage
            $table->string('nome_original')->nullable();   // nome do ficheiro original
            $table->string('status_quando_submetido');     // snapshot do status do trabalho na altura

            $table->timestamps();

            $table->unique(['trabalho_pap_id', 'numero_versao']);
            $table->index('trabalho_pap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabalho_pap_versoes');
    }
};
