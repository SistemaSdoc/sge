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
        Schema::table('sugestoes_temas_pap', function (Blueprint $table) {
            $table->enum('estado_aprovacao', ['pendente', 'aprovada', 'rejeitada', 'melhoria_solicitada'])
                ->default('pendente')
                ->after('ativo');
            $table->text('observacao_aprovacao')->nullable()->after('estado_aprovacao');
            $table->uuid('aprovado_por')->nullable()->after('observacao_aprovacao');
            $table->foreign('aprovado_por')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable()->after('aprovado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sugestoes_temas_pap', function (Blueprint $table) {
            $table->dropForeign(['aprovado_por']);
            $table->dropColumn(['estado_aprovacao', 'observacao_aprovacao', 'aprovado_por', 'aprovado_em']);
        });
    }
};
