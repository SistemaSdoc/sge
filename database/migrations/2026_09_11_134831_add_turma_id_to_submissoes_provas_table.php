<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            $table->uuid('turma_id')->nullable()->after('classe_id');
            $table->foreign('turma_id')->references('id')->on('turmas')->onDelete('cascade');
            $table->index(['prazo_prova_id', 'professor_id', 'turma_id']);
        });
    }

    public function down(): void
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            $table->dropForeign(['turma_id']);
            $table->dropIndex(['prazo_prova_id', 'professor_id', 'turma_id']);
            $table->dropColumn('turma_id');
        });
    }
};