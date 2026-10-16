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
        if (! Schema::hasTable('curso_tutelado_professor') || Schema::hasColumn('curso_tutelado_professor', 'opap')) {
            return;
        }

        Schema::table('curso_tutelado_professor', function (Blueprint $table) {
            $table->boolean('opap')->default(false)->after('coordenador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curso_tutelado_professor', function (Blueprint $table) {
            $table->dropColumn('opap');
        });
    }
};
