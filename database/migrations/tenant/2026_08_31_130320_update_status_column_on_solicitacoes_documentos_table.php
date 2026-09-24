<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE solicitacoes_documentos
            SET status = CASE status
                WHEN 'em_analise' THEN 'pendente'
                WHEN 'emitido' THEN 'aprovado'
                WHEN 'entregue' THEN 'aprovado'
                WHEN 'cancelado' THEN 'rejeitado'
                ELSE status
            END
            WHERE status IN ('em_analise', 'emitido', 'entregue', 'cancelado')
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE solicitacoes_documentos
            MODIFY status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente'
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE solicitacoes_documentos
            MODIFY status ENUM('pendente', 'em_analise', 'aprovado', 'rejeitado', 'emitido', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente'
        SQL);
    }
};
