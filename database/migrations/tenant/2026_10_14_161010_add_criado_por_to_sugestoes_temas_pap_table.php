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
        if (! Schema::hasTable('sugestoes_temas_pap') || Schema::hasColumn('sugestoes_temas_pap', 'criado_por')) {
            return;
        }

        Schema::table('sugestoes_temas_pap', function (Blueprint $table) {
            $table->foreignUuid('criado_por')
                ->nullable()
                ->after('curso_tutelado_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sugestoes_temas_pap') || ! Schema::hasColumn('sugestoes_temas_pap', 'criado_por')) {
            return;
        }

        Schema::table('sugestoes_temas_pap', function (Blueprint $table) {
            $table->dropConstrainedForeignId('criado_por');
        });
    }
};
