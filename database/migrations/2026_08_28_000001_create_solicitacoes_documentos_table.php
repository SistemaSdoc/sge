<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_documentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('aluno_id')->nullable()->constrained('alunos');
            $table->foreignUuid('curso_id')->nullable()->constrained('cursos');
            $table->foreignUuid('turma_id')->nullable()->constrained('turmas');
            $table->foreignUuid('classe_id')->nullable()->constrained('classes');
            $table->foreignUuid('ano_lectivo_id')->nullable()->constrained('ano_lectivos');

            $table->foreignUuid('instituicao_origem_id')->nullable()->constrained('instituicoes');
            $table->foreignUuid('instituicao_tutora_id')->nullable()->constrained('instituicoes');
            $table->foreignUuid('instituicao_aprovadora_id')->nullable()->constrained('instituicoes');
            $table->foreignUuid('instituicao_emissora_id')->nullable()->constrained('instituicoes');

            // Os tipos base estão em config/documentos.php — mantido aqui por compatibilidade
            $table->enum('tipo_documento', ['declaracao', 'historico', 'certificado', 'declaracao_com_notas'])->default('declaracao');
            $table->text('motivo');
            $table->text('observacoes')->nullable();

            $table->enum('status', ['pendente', 'em_analise', 'aprovado', 'rejeitado', 'emitido', 'entregue', 'cancelado'])->default('pendente');
            $table->string('numero_processo')->nullable();
            $table->string('numero_registro_tutora')->nullable();

            $table->timestamp('data_solicitacao')->nullable();
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamp('data_emissao')->nullable();

            // Rupe / pagamento fields
            $table->string('rupe_referencia')->nullable();
            $table->string('rupe_entidade')->nullable();
            $table->decimal('rupe_valor', 10, 2)->nullable();
            $table->timestamp('rupe_gerado_em')->nullable();
            $table->enum('estado_pagamento', ['pendente', 'pago'])->default('pendente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_documentos');
    }
};
