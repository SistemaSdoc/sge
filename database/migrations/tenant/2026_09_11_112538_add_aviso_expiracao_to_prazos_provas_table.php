<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prazos_provas', function (Blueprint $table) {
            $table->boolean('aviso_expiracao_enviado')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('prazos_provas', function (Blueprint $table) {
            $table->dropColumn('aviso_expiracao_enviado');
        });
    }
};