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
        Schema::table('curso_tutelado_shared', function (Blueprint $table) {
            $table->uuid('curso_id')->nullable()->after('curso_tutelado_tutelado_id');
            $table->index('curso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curso_tutelado_shared', function (Blueprint $table) {
            $table->dropIndex(['curso_id']);
            $table->dropColumn('curso_id');
        });
    }
};
