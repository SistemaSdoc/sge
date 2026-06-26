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
        Schema::table('turma_aluno', function (Blueprint $table) {
            // transita | transita_com_deficiencia | recurso | reprovado | EEF | incompleto | sem_notas
            $table->string('resultado')->nullable()->after('situacao');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turma_aluno', function (Blueprint $table) {
            //
        });
    }
};
