<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_pap', function (Blueprint $table) {
            // Renomeia o campo existente para deixar claro que é o geral
            $table->renameColumn('objectivos', 'objectivo_geral');

            // Adiciona o novo campo logo a seguir
            $table->text('objectivos_especificos')->nullable()->after('objectivo_geral');
        });
    }

    public function down(): void
    {
        Schema::table('grupo_pap', function (Blueprint $table) {
            $table->dropColumn('objectivos_especificos');
            $table->renameColumn('objectivo_geral', 'objectivos');
        });
    }
};
