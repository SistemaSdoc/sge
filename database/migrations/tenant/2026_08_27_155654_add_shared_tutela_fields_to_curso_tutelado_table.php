<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curso_tutelado', function (Blueprint $table): void {
            $table->uuid('instituicao_tutora_id')->nullable()->change();
            $table->string('tipo_tutela')->default('propria')->after('instituicao_tutora_id');
            $table->uuid('curso_tutelado_shared_id')->nullable()->after('tipo_tutela');
            $table->index('curso_tutelado_shared_id');
        });
    }

    public function down(): void
    {
        Schema::table('curso_tutelado', function (Blueprint $table): void {
            $table->dropIndex(['curso_tutelado_shared_id']);
            $table->dropColumn(['tipo_tutela', 'curso_tutelado_shared_id']);
            $table->uuid('instituicao_tutora_id')->nullable(false)->change();
        });
    }
};
