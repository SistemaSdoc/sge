<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reclamacoes_notas')) {
            return;
        }

        $this->alterStatusEnum([
            'pendente',
            'em_analise',
            'aguarda_informacao',
            'deferida',
            'indeferida',
            'aprovada',
            'reprovada',
        ]);

        DB::table('reclamacoes_notas')
            ->where('status', 'deferida')
            ->update(['status' => 'aprovada']);

        DB::table('reclamacoes_notas')
            ->where('status', 'indeferida')
            ->update(['status' => 'reprovada']);

        $this->alterStatusEnum();
    }

    public function down(): void
    {
        if (! Schema::hasTable('reclamacoes_notas')) {
            return;
        }

        $this->alterStatusEnum([
            'pendente',
            'em_analise',
            'aguarda_informacao',
            'deferida',
            'indeferida',
            'aprovada',
            'reprovada',
        ]);

        DB::table('reclamacoes_notas')
            ->where('status', 'aprovada')
            ->update(['status' => 'deferida']);

        DB::table('reclamacoes_notas')
            ->where('status', 'reprovada')
            ->update(['status' => 'indeferida']);

        $this->alterStatusEnum(['pendente', 'em_analise', 'aguarda_informacao', 'deferida', 'indeferida']);
    }

    private function alterStatusEnum(array $statuses = ['pendente', 'em_analise', 'aguarda_informacao', 'aprovada', 'reprovada']): void
    {
        $enum = implode("','", $statuses);

        DB::statement("ALTER TABLE reclamacoes_notas MODIFY status ENUM('{$enum}') NOT NULL DEFAULT 'pendente'");
    }
};
