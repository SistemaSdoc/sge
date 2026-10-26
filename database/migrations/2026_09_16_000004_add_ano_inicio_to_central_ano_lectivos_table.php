<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ano_lectivos', 'ano_inicio')) {
            Schema::table('ano_lectivos', function (Blueprint $table): void {
                $table->unsignedSmallInteger('ano_inicio')->nullable()->after('id');
            });
        }

        DB::table('ano_lectivos')->orderBy('id')->eachById(function (object $ano): void {
            DB::table('ano_lectivos')
                ->where('id', $ano->id)
                ->update(['ano_inicio' => (int) date('Y', strtotime($ano->data_inicio))]);
        });

        $hasUniqueIndex = collect(Schema::getIndexes('ano_lectivos'))
            ->contains('name', 'ano_lectivos_ano_inicio_deleted_at_unique');

        if (! $hasUniqueIndex) {
            Schema::table('ano_lectivos', function (Blueprint $table): void {
                $table->unique(
                    ['ano_inicio', 'deleted_at'],
                    'ano_lectivos_ano_inicio_deleted_at_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('ano_lectivos', function (Blueprint $table): void {
            $table->dropUnique('ano_lectivos_ano_inicio_deleted_at_unique');
            $table->dropColumn('ano_inicio');
        });
    }
};
