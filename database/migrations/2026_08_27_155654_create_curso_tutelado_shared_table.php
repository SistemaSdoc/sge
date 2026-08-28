<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_tutelado_shared', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_tutor_id');
            $table->string('tenant_tutelado_id');
            $table->uuid('curso_tutelado_tutelado_id');
            $table->string('curso_nome');
            $table->unsignedTinyInteger('duracao_anos');
            $table->string('status')->default('activo');
            $table->timestamps();

            $table->index('tenant_tutor_id');
            $table->index('tenant_tutelado_id');
            $table->unique([
                'tenant_tutor_id',
                'tenant_tutelado_id',
                'curso_tutelado_tutelado_id',
            ], 'curso_tutelado_shared_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_tutelado_shared');
    }
};
