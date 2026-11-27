<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ano_lectivos')
            ->whereNotNull('ano_inicio')
            ->update([
                'nome' => DB::raw("CONCAT(ano_inicio, '/', ano_inicio + 1)"),
            ]);
    }

    public function down(): void
    {
        // Names are derived from ano_inicio and are intentionally not restored.
    }
};
