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
        if (Schema::hasColumn('candidatos', 'portador_deficiencia')) {
            Schema::table('candidatos', function (Blueprint $table) {
                $table->dropColumn('portador_deficiencia');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('candidatos', 'portador_deficiencia')) {
            Schema::table('candidatos', function (Blueprint $table) {
                $table->boolean('portador_deficiencia')->default(false);
            });
        }
    }
};
