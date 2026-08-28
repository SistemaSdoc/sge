<?php

namespace App\Http\Controllers\Central;

use App\Helpers\CentralDatabase;
use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\Tutela;
use App\Services\Tenant\TenantInstituicaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TutelaController extends Controller
{
    public function instituicoes(TenantInstituicaoService $tenantInstituicaoService): JsonResponse
    {
        return response()->json([
            'data' => $tenantInstituicaoService->listarTodas(),
        ]);
    }

    public function cursosPorInstituicao(string $instituicaoId): JsonResponse
    {
        $tenants = CentralDatabase::connection()
            ->table('tenants')
            ->whereIn('status', ['active', 'trial'])
            ->get();

        $tenant = $tenants->first(function (object $tenantRow) use ($instituicaoId): bool {
            $tenant = Tenant::find($tenantRow->id);

            if (! $tenant) {
                return false;
            }

            tenancy()->initialize($tenant);

            try {
                return DB::table('instituicoes')
                    ->where('id', $instituicaoId)
                    ->exists();
            } finally {
                tenancy()->end();
            }
        });

        abort_unless($tenant, 404, 'Instituição não encontrada.');

        $tenantModel = Tenant::find($tenant->id);

        if (! $tenantModel) {
            abort(404, 'Tenant não encontrado.');
        }

        tenancy()->initialize($tenantModel);

        try {
            $cursos = DB::table('cursos')
                ->join('instituicao_curso', 'cursos.id', '=', 'instituicao_curso.curso_id')
                ->where('instituicao_curso.instituicao_id', $instituicaoId)
                ->select('cursos.id', 'cursos.nome')
                ->orderBy('cursos.nome')
                ->get();
        } finally {
            tenancy()->end();
        }

        return response()->json(['data' => $cursos]);
    }

    public function tutelas(TenantInstituicaoService $tenantInstituicaoService): JsonResponse
    {
        $instituicoes = $tenantInstituicaoService->listarTodas()->keyBy('id');

        $tutelas = Tutela::query()
            ->where('ativo', true)
            ->get(['id', 'instituicao_tutora_id', 'instituicao_tutelada_id', 'curso_id', 'ativo']);

        $data = $tutelas->map(function (Tutela $tutela) use ($instituicoes): ?array {
            $instituicaoTutelada = $instituicoes->get($tutela->instituicao_tutelada_id);

            if (! $instituicaoTutelada) {
                return null;
            }

            $tenant = Tenant::find($instituicaoTutelada['tenant_id']);

            if (! $tenant) {
                return null;
            }

            tenancy()->initialize($tenant);

            try {
                $curso = DB::table('cursos')
                    ->join('instituicao_curso', 'cursos.id', '=', 'instituicao_curso.curso_id')
                    ->where('instituicao_curso.instituicao_id', $tutela->instituicao_tutelada_id)
                    ->where('cursos.id', $tutela->curso_id)
                    ->first(['cursos.id', 'cursos.nome']);
            } finally {
                tenancy()->end();
            }

            return [
                'id' => $tutela->id,
                'curso' => $curso,
                'instituicao_tutora' => $instituicoes->get($tutela->instituicao_tutora_id),
                'instituicao_tutelada' => $instituicaoTutelada,
                'ativo' => $tutela->ativo,
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }
}
