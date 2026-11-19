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
        Schema::table('periodo_lancamento_notas', function (Blueprint $table): void {
            $table->timestamp('quadro_honra_processado_em')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodo_lancamento_notas', function (Blueprint $table): void {
            $table->dropColumn('quadro_honra_processado_em');
        });
    }
};
