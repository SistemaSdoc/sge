<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prazos_provas', function (Blueprint $table) {
            $table->uuid('instituicao_id')->nullable()->after('id');
            $table->foreign('instituicao_id')->references('id')->on('instituicoes')->onDelete('cascade');
            $table->index('instituicao_id');
        });

        // Preencher instituicao_id dos prazos existentes (a partir do criador)
        DB::statement("
            UPDATE prazos_provas p
            INNER JOIN users u ON u.id = p.criado_por
            SET p.instituicao_id = u.instituicao_id
            WHERE p.instituicao_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('prazos_provas', function (Blueprint $table) {
            $table->dropForeign(['instituicao_id']);
            $table->dropIndex(['instituicao_id']);
            $table->dropColumn('instituicao_id');
        });
    }
};