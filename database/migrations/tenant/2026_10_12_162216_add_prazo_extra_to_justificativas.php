<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->timestamp('prazo_prorrogado_ate')
                ->nullable()
                ->after('parecer_diretor');

            $table->index('prazo_prorrogado_ate');
        });
    }

    public function down(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->dropIndex(['prazo_prorrogado_ate']);
            $table->dropColumn('prazo_prorrogado_ate');
        });
    }
};