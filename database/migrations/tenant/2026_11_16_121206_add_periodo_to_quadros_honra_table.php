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
        Schema::table('quadros_honra', function (Blueprint $table): void {
            $table->unsignedTinyInteger('periodo')->nullable()->after('escopo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quadros_honra', function (Blueprint $table): void {
            $table->dropColumn('periodo');
        });
    }
};
