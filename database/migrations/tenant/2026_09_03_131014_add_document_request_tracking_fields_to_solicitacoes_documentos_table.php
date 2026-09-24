<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitacoes_documentos', function (Blueprint $table) {
            if (! Schema::hasColumn('solicitacoes_documentos', 'data_pagamento_confirmado')) {
                $table->timestamp('data_pagamento_confirmado')->nullable()->after('data_aprovacao');
            }

            if (! Schema::hasColumn('solicitacoes_documentos', 'data_pronto')) {
                $table->timestamp('data_pronto')->nullable()->after('data_emissao');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitacoes_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('solicitacoes_documentos', 'data_pronto')) {
                $table->dropColumn('data_pronto');
            }

            if (Schema::hasColumn('solicitacoes_documentos', 'data_pagamento_confirmado')) {
                $table->dropColumn('data_pagamento_confirmado');
            }
        });
    }
};
