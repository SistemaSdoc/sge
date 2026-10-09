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
        if (! Schema::hasTable('justificativas_nao_submissao')) {
            return;
        }

        if (Schema::hasColumn('justificativas_nao_submissao', 'parecer_diretor')) {
            return;
        }

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
