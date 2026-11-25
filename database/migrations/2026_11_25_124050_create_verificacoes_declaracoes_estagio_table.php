<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('verificacoes_declaracoes_estagio', function (Blueprint $table): void {
            $table->id();
            $table->char('token_hash', 64);
            $table->string('ip_origem', 45)->nullable();
            $table->boolean('valida');
            $table->timestamp('verificado_em');
            $table->timestamps();

            $table->index(['token_hash', 'verificado_em']);
            $table->index(['ip_origem', 'verificado_em']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('verificacoes_declaracoes_estagio');
    }
};
