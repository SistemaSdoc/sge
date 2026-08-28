<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_pagaveis', function (Blueprint $table) {
            $table->unsignedInteger('multa_dias_tolerancia')->nullable()->after('frequencia');
            $table->decimal('multa_valor', 10, 2)->nullable()->after('multa_dias_tolerancia');
        });
    }

    public function down(): void
    {
        Schema::table('itens_pagaveis', function (Blueprint $table) {
            $table->dropColumn(['multa_dias_tolerancia', 'multa_valor']);
        });
    }
};
