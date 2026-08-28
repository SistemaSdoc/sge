<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('tenancy.database.central_connection', config('database.default')))
            ->create('tutelas', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('instituicao_tutora_id')->index();
                $table->uuid('instituicao_tutelada_id')->index();
                $table->uuid('curso_id')->index();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->unique([
                    'instituicao_tutora_id',
                    'instituicao_tutelada_id',
                    'curso_id',
                ], 'tutelas_instituicoes_curso_unique');
            });
    }

    public function down(): void
    {
        Schema::connection(config('tenancy.database.central_connection', config('database.default')))
            ->dropIfExists('tutelas');
    }
};
