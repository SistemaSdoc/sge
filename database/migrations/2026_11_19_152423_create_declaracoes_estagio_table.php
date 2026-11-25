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
        Schema::create('declaracoes_estagio', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('instituicao_nome');
            $table->string('instituicao_tipo', 20);
            $table->uuid('aluno_id')->index();
            $table->string('aluno_nome');
            $table->string('matricula')->nullable();
            $table->string('numero_processo')->nullable();
            $table->string('empresa_nome')->default('Tunganetu');
            $table->string('public_token', 64)->unique();
            $table->uuid('gerado_por_id')->nullable()->index();
            $table->timestamp('emitido_em');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaracoes_estagio');
    }
};
