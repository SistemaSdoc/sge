<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curso_tutelado') || Schema::hasColumn('curso_tutelado', 'sugestoes_temas_pap_path')) {
            return;
        }

        Schema::table('curso_tutelado', function (Blueprint $table) {
            $table->string('sugestoes_temas_pap_path')->nullable()->after('estrutura_trabalho_pap_path');
        });
    }

    public function down(): void
    {
        Schema::table('curso_tutelado', function (Blueprint $table) {
            $table->dropColumn('sugestoes_temas_pap_path');
        });
    }
};
