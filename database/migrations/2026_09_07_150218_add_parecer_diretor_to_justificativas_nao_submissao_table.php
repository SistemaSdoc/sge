<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
        $table->text('parecer_diretor')->nullable()->after('data_avaliacao');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('justificativas_nao_submissao', function (Blueprint $table) {
            $table->dropColumn('parecer_diretor');
        });
    }
};
