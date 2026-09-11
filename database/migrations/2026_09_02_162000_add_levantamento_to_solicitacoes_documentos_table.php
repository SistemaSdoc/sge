<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_documentos', function (Blueprint $table) {
            if (! Schema::hasColumn('solicitacoes_documentos', 'data_levantamento')) {
                $table->timestamp('data_levantamento')->nullable()->after('data_emissao');
            }

            if (! Schema::hasColumn('solicitacoes_documentos', 'levantado_por_id')) {
                $table->uuid('levantado_por_id')->nullable()->after('data_levantamento');
                $table->foreign('levantado_por_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('solicitacoes_documentos', 'levantado_por_id')) {
                $table->dropForeign(['levantado_por_id']);
                $table->dropColumn('levantado_por_id');
            }

            if (Schema::hasColumn('solicitacoes_documentos', 'data_levantamento')) {
                $table->dropColumn('data_levantamento');
            }
        });
    }
};
