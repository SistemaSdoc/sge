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
            $table->uuid('ano_lectivo_id')->nullable()->after('turma_id');
            $table->foreign('ano_lectivo_id')->references('id')->on('ano_lectivos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turma_aluno', function (Blueprint $table) {
            $table->dropForeign(['ano_lectivo_id']);
            $table->dropColumn('ano_lectivo_id');
        });
    }
};
