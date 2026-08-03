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
        Schema::create('solicitacoes_edicao_pauta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('turma_disciplina_professor_id')
                ->constrained('turma_disciplina_professor');
            $table->unsignedTinyInteger('periodo');
            $table->enum('tipo', ['reabertura_edicao', 'extensao_prazo'])->default('extensao_prazo');
            $table->foreignUuid('professor_user_id')->constrained('users');
            $table->text('motivo');
            $table->enum('status', ['pendente', 'aprovada', 'rejeitada'])->default('pendente');
            $table->foreignUuid('decidido_por')->nullable()->constrained('users');
            $table->timestamp('decidido_em')->nullable();
            $table->timestamp('prazo_edicao_ate')->nullable(); // para extensao de prazo
            $table->text('observacao')->nullable();
            $table->timestamp('usada_em')->nullable(); // quando o professor finalizou após autorização
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_edicao_pauta');
    }
};
