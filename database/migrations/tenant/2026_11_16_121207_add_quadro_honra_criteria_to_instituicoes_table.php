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
        Schema::table('instituicoes', function (Blueprint $table): void {
            $table->decimal('quadro_honra_media_minima', 4, 2)->default(14);
            $table->unsignedSmallInteger('quadro_honra_limite_alunos')->default(10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instituicoes', function (Blueprint $table): void {
            $table->dropColumn(['quadro_honra_media_minima', 'quadro_honra_limite_alunos']);
        });
    }
};
