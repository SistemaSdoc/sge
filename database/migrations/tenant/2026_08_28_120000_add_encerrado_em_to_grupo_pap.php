<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_pap', function (Blueprint $table): void {
            $table->timestamp('encerrado_em')->nullable()->after('local_defesa');
            $table->index('encerrado_em');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE grupo_pap MODIFY COLUMN status_aprovacao ENUM('rascunho', 'submetido', 'pendente', 'aprovado', 'reprovado', 'melhoria-solicitada', 'arquivado') NOT NULL DEFAULT 'rascunho'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE grupo_pap MODIFY COLUMN status_aprovacao ENUM('rascunho', 'submetido', 'pendente', 'aprovado', 'reprovado', 'melhoria-solicitada') NOT NULL DEFAULT 'rascunho'");
        }

        Schema::table('grupo_pap', function (Blueprint $table): void {
            $table->dropIndex(['encerrado_em']);
            $table->dropColumn('encerrado_em');
        });
    }
};
