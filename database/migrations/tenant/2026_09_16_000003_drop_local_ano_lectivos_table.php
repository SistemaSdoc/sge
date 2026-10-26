<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ano_lectivos');
    }

    public function down(): void
    {
        // The central academic-year table is the only supported source of truth.
    }
};
