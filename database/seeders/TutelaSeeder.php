<?php

namespace Database\Seeders;

use App\Models\Central\Tenant;
use App\Models\Central\Tutela;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TutelaSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = Tenant::query()
            ->whereIn('status', ['active', 'trial'])
            ->get()
            ->map(function (Tenant $tenant): ?object {
                try {
                    tenancy()->initialize($tenant);

                    $institution = DB::table('instituicoes')
                        ->select(['id', 'nome'])
                        ->first();

                    if (! $institution) {
                        return null;
                    }

                    $course = DB::table('cursos')
                        ->select('id')
                        ->first();

                    return $course ? (object) [
                        'tenant_id' => $tenant->id,
                        'instituicao_id' => $institution->id,
                        'instituicao_nome' => $institution->nome,
                        'curso_id' => $course->id,
                    ] : null;
                } finally {
                    tenancy()->end();
                }
            })
            ->filter()
            ->values();

        if ($institutions->count() < 2) {
            return;
        }

        $tutora = $institutions->first();
        $tutelada = $institutions->skip(1)->first();

        Tutela::query()->updateOrCreate(
            [
                'instituicao_tutora_id' => $tutora->instituicao_id,
                'instituicao_tutelada_id' => $tutelada->instituicao_id,
                'curso_id' => $tutelada->curso_id,
            ],
            [
                'ativo' => true,
            ]
        );
    }
}
