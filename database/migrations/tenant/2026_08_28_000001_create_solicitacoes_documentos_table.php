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

            // [ALTERADO] curso_id vive no catálogo central (outra base de dados): sem FK, só índice
            $table->uuid('curso_id')->nullable()->index();

            $table->foreignUuid('turma_id')->nullable()->constrained('turmas');
            $table->foreignUuid('classe_id')->nullable()->constrained('classes');
            $table->foreignUuid('ano_lectivo_id')->nullable()->constrained('ano_lectivos');

            // instituicao_origem_id é o próprio colégio (existe no tenant): mantém a FK
            $table->foreignUuid('instituicao_origem_id')->nullable()->constrained('instituicoes');

            // [ALTERADO] tutora/aprovadora/emissora podem ser o instituto (outro tenant): sem FK, só índice
            $table->uuid('instituicao_tutora_id')->nullable()->index();
            $table->uuid('instituicao_aprovadora_id')->nullable()->index();
            $table->uuid('instituicao_emissora_id')->nullable()->index();

            // Os tipos base estão em config/documentos.php — mantido aqui por compatibilidade
            $table->enum('tipo_documento', ['declaracao', 'historico', 'certificado', 'declaracao_com_notas'])->default('declaracao');
            $table->text('motivo');
            $table->text('observacoes')->nullable();

            $table->enum('status', ['pendente', 'em_analise', 'aprovado', 'rejeitado', 'emitido', 'entregue', 'cancelado'])->default('pendente');
            $table->string('numero_processo')->nullable();
            $table->string('numero_registro_tutora')->nullable();

            $table->timestamp('data_solicitacao')->nullable();
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamp('data_encaminhamento')->nullable(); // <-- NOVO
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