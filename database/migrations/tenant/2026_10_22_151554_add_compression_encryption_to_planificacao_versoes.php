<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planificacao_versoes', function (Blueprint $table) {
            $table->unsignedBigInteger('tamanho_original_bytes')->nullable()->after('tamanho_bytes');
            $table->boolean('comprimido')->default(false)->after('tamanho_original_bytes');
            $table->boolean('encriptado')->default(false)->after('comprimido');
            $table->string('algoritmo_encriptacao', 50)->nullable()->after('encriptado');
        });
    }

    public function down(): void
    {
        Schema::table('planificacao_versoes', function (Blueprint $table) {
            $table->dropColumn([
                'tamanho_original_bytes',
                'comprimido',
                'encriptado',
                'algoritmo_encriptacao',
            ]);
        });
    }
};