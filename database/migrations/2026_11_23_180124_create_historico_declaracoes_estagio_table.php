<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_declaracoes_estagio', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->uuid('aluno_id');
            $table->string('aluno_nome');
            $table->string('matricula')->nullable();
            $table->string('gerado_por_id')->nullable();
            $table->timestamp('emitido_em');
            $table->timestamps();

            $table->index(['tenant_id', 'emitido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_declaracoes_estagio');
    }
};
