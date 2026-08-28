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
        Schema::table('curso_tutelado_shared', function (Blueprint $table): void {
            $table->string('tenant_tutor_nome')->nullable()->after('tenant_tutor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curso_tutelado_shared', function (Blueprint $table): void {
            $table->dropColumn('tenant_tutor_nome');
        });
    }
};
