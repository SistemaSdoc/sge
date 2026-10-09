<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar coluna nullable
        if (! Schema::hasColumn('planificacoes', 'curso_id')) {
            Schema::table('planificacoes', function (Blueprint $table) {
                $table->uuid('curso_id')->nullable()->after('instituicao_id');
                $table->index('curso_id');
            });
        }

        // 2. Dropar o unique antigo
        Schema::table('planificacoes', function (Blueprint $table) {
            $table->dropUnique('planificacoes_chave_unica');
        });

        // 3. Criar o novo unique com curso_id
        Schema::table('planificacoes', function (Blueprint $table) {
            $table->unique(
                ['ano_letivo_id', 'curso_id', 'disciplina_id', 'classe_id', 'periodo'],
                'planificacoes_chave_unica'
            );
        });

        // 4. Backfill: preencher curso_id nas planificações existentes
        //    Assumindo que a classe+disciplina só existe num curso (senão fica NULL)
        DB::statement("
            UPDATE planificacoes p
            SET curso_id = (
                SELECT DISTINCT cc.curso_tutelado_id
                FROM classe_turno_disciplina ctd
                JOIN curso_classe_turno cct ON cct.id = ctd.curso_classe_turno_id
                JOIN curso_classe cc ON cc.id = cct.curso_classe_id
                WHERE cc.classe_id = p.classe_id
                  AND ctd.disciplina_id = p.disciplina_id
                LIMIT 1
            )
            WHERE p.curso_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('planificacoes', function (Blueprint $table) {
            $table->dropUnique('planificacoes_chave_unica');
            $table->dropIndex(['curso_id']);
            $table->dropColumn('curso_id');
        });

        Schema::table('planificacoes', function (Blueprint $table) {
            $table->unique(
                ['ano_letivo_id', 'disciplina_id', 'classe_id', 'periodo'],
                'planificacoes_chave_unica'
            );
        });
    }
};