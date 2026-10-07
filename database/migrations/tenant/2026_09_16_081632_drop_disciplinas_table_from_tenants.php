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

        if (Schema::hasTable('classe_turno_disciplina')) {
            $database = DB::connection()->getDatabaseName();
            $foreignKeyExists = DB::selectOne(
                'select 1 from information_schema.TABLE_CONSTRAINTS where CONSTRAINT_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = \'FOREIGN KEY\' limit 1',
                [$database, 'classe_turno_disciplina', 'classe_turno_disciplina_disciplina_id_foreign'],
            );

            if ($foreignKeyExists) {
                Schema::table('classe_turno_disciplina', function ($table): void {
                    $table->dropForeign('classe_turno_disciplina_disciplina_id_foreign');
                });
            }
        }

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

            if (Schema::hasTable('classe_turno_disciplina')) {
                DB::table('classe_turno_disciplina')
                    ->where('disciplina_id', $disciplina->id)
                    ->update(['disciplina_id' => $centralDisciplina->id]);
            }
        }

        Schema::dropIfExists('disciplinas');
    }

    public function down(): void
    {
        // O catálogo de disciplinas pertence à base central.
    }
};
