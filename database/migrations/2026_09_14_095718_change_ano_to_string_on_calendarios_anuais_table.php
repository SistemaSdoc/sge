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
        Schema::table('calendarios_anuais', function (Blueprint $table): void {
            $table->string('ano', 9)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendarios_anuais', function (Blueprint $table): void {
            $table->unsignedInteger('ano')->change();
        });
    }
};
