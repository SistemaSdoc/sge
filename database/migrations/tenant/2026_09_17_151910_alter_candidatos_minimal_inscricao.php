<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            // numero_estudante passa a nullable
            $table->string('numero_estudante')->nullable()->change();

            // flag de perfil completo
            $table->boolean('perfil_completo')->default(false)->after('morada');
        });

        // Preenche o flag para candidatos que já têm perfil completo
        DB::table('candidatos')
            ->whereNotNull('data_nascimento')
            ->whereNotNull('genero')
            ->whereNotNull('municipio')
            ->update(['perfil_completo' => true]);
    }

    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->string('numero_estudante')->nullable(false)->change();
            $table->dropColumn('perfil_completo');
        });
    }
};