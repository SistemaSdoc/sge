<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('declaracoes_estagio', 'carga_horaria_diaria')) {
            Schema::table('declaracoes_estagio', function (Blueprint $table): void {
                $table->dropColumn('carga_horaria_diaria');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('declaracoes_estagio')
            && ! Schema::hasColumn('declaracoes_estagio', 'carga_horaria_diaria')
        ) {
            Schema::table('declaracoes_estagio', function (Blueprint $table): void {
                $table->decimal('carga_horaria_diaria', 4, 1)->nullable();
            });
        }
    }
};
