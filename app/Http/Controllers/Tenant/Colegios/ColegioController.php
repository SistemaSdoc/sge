<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\Tutela;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use App\Services\Tenant\TenantInstituicaoService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ColegioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        TenantInstituicaoService $tenantInstituicaoService
    ) {
        $instituicao = Instituicao::findOrFail($request->query('instituicao'));
        $instituicoes = $tenantInstituicaoService->listarTodas()->keyBy('id');
        $colegios = Tutela::query()
            ->where('instituicao_tutora_id', $instituicao->id)
            ->where('ativo', true)
            ->get(['instituicao_tutelada_id', 'curso_id'])
            ->groupBy('instituicao_tutelada_id')
            ->map(function ($tutelas, string $instituicaoTuteladaId) use ($instituicoes) {
                $colegio = $instituicoes->get($instituicaoTuteladaId);

                return $colegio ? [
                    'id' => $colegio['id'],
                    'nome' => $colegio['nome'],
                    'tipo' => 'colegio',
                    'total_cursos' => $tutelas->count(),
                ] : null;
            })
            ->filter()
            ->sortBy('nome')
            ->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $colegios = new LengthAwarePaginator(
            $colegios->forPage($currentPage, 5)->values(),
            $colegios->count(),
            5,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('tenant/colegio/index', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
            ],
            'colegios' => $colegios,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Request $request,
        string $colegio,
        TenantInstituicaoService $tenantInstituicaoService
    ) {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $instituicaoModel = Instituicao::findOrFail($request->query('instituicao'));
        $instituicoes = $tenantInstituicaoService->listarTodas()->keyBy('id');
        $colegioData = $instituicoes->get($colegio);

        abort_unless($colegioData, 404, 'Instituição tutelada não encontrada.');

        $tenantAtual = tenancy()->tenant;
        $tenantTutelado = Tenant::find($colegioData['tenant_id']);

        if (! $tenantTutelado) {
            abort(404, 'Tenant tutelado não encontrado.');
        }

        tenancy()->initialize($tenantTutelado);

        try {
            $tutelas = Tutela::query()
                ->where('instituicao_tutora_id', $instituicaoModel->id)
                ->where('instituicao_tutelada_id', $colegio)
                ->where('ativo', true)
                ->get(['curso_id']);

            $cursos = DB::table('instituicao_curso')
                ->join('cursos', 'cursos.id', '=', 'instituicao_curso.curso_id')
                ->leftJoin('curso_tutelado', 'curso_tutelado.instituicao_curso_id', '=', 'instituicao_curso.id')
                ->where('instituicao_curso.instituicao_id', $colegio)
                ->whereIn('cursos.id', $tutelas->pluck('curso_id'))
                ->select('cursos.id as curso_id', 'cursos.nome', 'curso_tutelado.id as curso_tutelado_id')
                ->paginate(10);
        } finally {
            if ($tenantAtual) {
                tenancy()->initialize($tenantAtual);
            } else {
                tenancy()->end();
            }
        }

        $cursosFormatados = $cursos->getCollection()->map(fn ($curso) => [
            'id' => $curso->curso_tutelado_id,
            'nome' => $curso->nome,
            'curso_tutelado_id' => $curso->curso_tutelado_id,
            'curso_id' => $curso->curso_id,
        ]);

        $cursos->setCollection($cursosFormatados);

        return Inertia::render('tenant/colegio/show', [
            'instituicao' => [
                'id' => $instituicaoModel->id,
                'nome' => $instituicaoModel->nome,
            ],
            'colegio' => [
                'id' => $colegioData['id'],
                'nome' => $colegioData['nome'],
            ],
            'can' => [
                'gerir_prazos' => $user->can('pautas.gerirPrazos'),
            ],
            'cursos' => $cursos,
        ]);
    }
}
