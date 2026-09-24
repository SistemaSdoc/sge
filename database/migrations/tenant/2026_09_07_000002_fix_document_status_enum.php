<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE solicitacoes_documentos
            MODIFY status ENUM(
                'pendente',
                'aprovado',
                'rejeitado',
                'pago',
                'pronto',
                'entregue',
                'em_analise',
                'emitido',
                'cancelado'
            ) NOT NULL DEFAULT 'pendente'
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE solicitacoes_documentos
            MODIFY status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente'
        SQL);
    }
};
