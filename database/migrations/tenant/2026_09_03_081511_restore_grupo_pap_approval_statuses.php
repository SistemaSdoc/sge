<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE grupo_pap MODIFY COLUMN status_aprovacao ENUM(
            'rascunho',
            'submetido',
            'pendente',
            'aprovado',
            'reprovado',
            'melhoria-solicitada',
            'melhoria-solicitada-tutor',
            'melhoria-solicitada-coordenacao',
            'arquivado'
        ) NOT NULL DEFAULT 'rascunho'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally leave the expanded enum in place to avoid invalidating stored states.
    }
};
