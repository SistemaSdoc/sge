<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('justificativas_nao_submissao', 'turma_id')) {
            Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
                $table->uuid('turma_id')->nullable()->after('prazo_prova_id');
                $table->index('turma_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('justificativas_nao_submissao', 'turma_id')) {
            Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
                $table->dropIndex(['turma_id']);
                $table->dropColumn('turma_id');
            });
        }
    }
};