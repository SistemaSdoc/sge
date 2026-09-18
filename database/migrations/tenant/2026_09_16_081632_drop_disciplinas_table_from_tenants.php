<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disciplinas')) {
            return;
        }

        $centralConnection = config('tenancy.database.central_connection');

        $database = DB::connection()->getDatabaseName();
        $foreignKeys = DB::select(
            'select TABLE_NAME as table_name, COLUMN_NAME as column_name, CONSTRAINT_NAME as constraint_name from information_schema.KEY_COLUMN_USAGE where CONSTRAINT_SCHEMA = ? and REFERENCED_TABLE_NAME = ? and REFERENCED_COLUMN_NAME = ? and REFERENCED_TABLE_SCHEMA = ? order by TABLE_NAME, CONSTRAINT_NAME',
            [$database, 'disciplinas', 'id', $database],
        );

        foreach (DB::table('disciplinas')->get() as $disciplina) {
            $centralDisciplina = DB::connection($centralConnection)
                ->table('disciplinas')
                ->where('nome', $disciplina->nome)
                ->first();

            if (! $centralDisciplina) {
                DB::connection($centralConnection)->table('disciplinas')->insert([
                    'id' => $disciplina->id,
                    'nome' => $disciplina->nome,
                    'sigla' => $disciplina->sigla,
                    'componente' => $disciplina->componente,
                    'carga_horaria' => 60,
                    'status' => 1,
                    'created_at' => $disciplina->created_at,
                    'updated_at' => $disciplina->updated_at,
                ]);

                $centralDisciplina = (object) ['id' => $disciplina->id];
            }

            foreach ($foreignKeys as $foreignKey) {
                DB::table($foreignKey->table_name)
                    ->where($foreignKey->column_name, $disciplina->id)
                    ->update([$foreignKey->column_name => $centralDisciplina->id]);
            }
        }

        foreach ($foreignKeys as $foreignKey) {
            Schema::table($foreignKey->table_name, function ($table) use ($foreignKey): void {
                $table->dropForeign($foreignKey->constraint_name);
            });
        }

        Schema::dropIfExists('disciplinas');
    }

    public function down(): void
    {
        // O catálogo de disciplinas pertence à base central.
    }
};
