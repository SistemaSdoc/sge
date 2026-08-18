<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE inscricoes MODIFY COLUMN status ENUM('pendente', 'apto_prova', 'aprovado', 'reprovado', 'reprovado_prova', 'cancelado') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inscricoes MODIFY COLUMN status ENUM('pendente', 'apto_prova', 'aprovado', 'reprovado', 'reprovado_prova') NOT NULL DEFAULT 'pendente'");
    }
};
