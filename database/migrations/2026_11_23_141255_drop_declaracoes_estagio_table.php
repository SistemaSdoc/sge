<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('declaracoes_estagio');
    }

    public function down(): void
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
            $table->string('bi')->nullable();
            $table->string('curso')->nullable();
            $table->string('ano')->nullable();
            $table->decimal('nota', 3, 1)->nullable();
            $table->string('avaliacao')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->decimal('carga_horaria_diaria', 4, 1)->nullable();
            $table->decimal('carga_horaria_total', 6, 1)->nullable();
            $table->string('responsavel_nome')->nullable();
            $table->string('responsavel_cargo')->nullable();
            $table->string('empresa_nome')->default('Tunganetu');
            $table->string('public_token', 64)->unique();
            $table->uuid('gerado_por_id')->nullable()->index();
            $table->timestamp('emitido_em');
            $table->timestamps();
        });
    }
};
