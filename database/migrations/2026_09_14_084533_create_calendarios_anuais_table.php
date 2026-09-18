<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendarios_anuais', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('ano', 9)->unique();
            $table->string('ficheiro_path');
            $table->string('ficheiro_nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendarios_anuais');
    }
};
