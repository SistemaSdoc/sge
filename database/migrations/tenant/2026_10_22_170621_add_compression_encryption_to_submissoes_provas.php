<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            // Tamanhos agregados (prova + chave somados)
            $table->unsignedBigInteger('tamanho_original_bytes')->nullable()->after('caminho_chave');
            $table->unsignedBigInteger('tamanho_bytes')->nullable()->after('tamanho_original_bytes');

            // Flags de compressão/encriptação
            $table->boolean('comprimido')->default(false)->after('tamanho_bytes');
            $table->boolean('encriptado')->default(false)->after('comprimido');
            $table->string('algoritmo_encriptacao', 50)->nullable()->after('encriptado');
        });
    }

    public function down(): void
    {
        Schema::table('submissoes_provas', function (Blueprint $table) {
            $table->dropColumn([
                'tamanho_original_bytes',
                'tamanho_bytes',
                'comprimido',
                'encriptado',
                'algoritmo_encriptacao',
            ]);
        });
    }
};