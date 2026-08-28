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

        DB::statement("ALTER TABLE inscricoes MODIFY COLUMN status ENUM('pendente', 'apto_prova', 'aprovado', 'reprovado', 'reprovado_prova', 'cancelado') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE inscricoes MODIFY COLUMN status ENUM('pendente', 'apto_prova', 'aprovado', 'reprovado', 'reprovado_prova') NOT NULL DEFAULT 'pendente'");
    }
};
