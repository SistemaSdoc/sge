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
        Schema::table('historico_aprovacao_pap', function (Blueprint $table) {
            $table->renameColumn('objectivos', 'objectivo_geral');
            $table->text('objectivos_especificos')->nullable()->after('objectivo_geral');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_aprovacao_pap', function (Blueprint $table) {
            $table->dropColumn('objectivos_especificos');
            $table->renameColumn('objectivo_geral', 'objectivos');
        });
    }
};
