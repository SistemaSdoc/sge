<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\User;
use App\Services\Central\TenantService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ColegioController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Instituicao $instituicao)
    {
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('status', 'activo')
            ->get();

        $colegios = $shared
            ->groupBy('tenant_tutelado_id')
            ->map(function ($vinculos, string $tenantTuteladoId): array {
                $tenant = Tenant::query()->find($tenantTuteladoId);
                $colegio = $tenant ? $this->tenantService->getInstituicao($tenant) : null;

                return [
                    'id' => $colegio?->id ?? $tenantTuteladoId,
                    'nome' => $colegio?->nome ?? $tenantTuteladoId,
                    'tipo' => $colegio?->tipo,
                    'tenant_id' => $tenantTuteladoId,
                    'total_cursos' => $vinculos->unique('curso_tutelado_tutelado_id')->count(),
                    'cursos' => [],
                ];
            })
            ->filter(fn (array $colegio): bool => $colegio['tipo'] === 'colegio')
            ->sortBy('nome')
            ->values();

        $perPage = 5;
        $currentPage = (int) $request->input('page', 1);
        $colegios = new LengthAwarePaginator(
            $colegios->forPage($currentPage, $perPage)->values(),
            $colegios->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()],
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
    public function show(Request $request, string $colegio)
    {
        /** @var User $user */
        $user = Auth::guard('tenant')->user();

        $instituicao = Instituicao::findOrFail($user->instituicao_id);
        $tenantTutorId = (string) tenancy()->tenant->getTenantKey();
        $tenantTutelado = Tenant::query()
            ->where('instituicao_id', $colegio)
            ->firstOrFail();
        $shared = CursoTuteladoShared::query()
            ->where('tenant_tutor_id', $tenantTutorId)
            ->where('tenant_tutelado_id', $tenantTutelado->getTenantKey())
            ->where('status', 'activo')
            ->get(['id', 'curso_tutelado_tutelado_id', 'status']);
        $sharedCursoIds = $shared->pluck('curso_tutelado_tutelado_id');
        $sharedPorCurso = $shared->keyBy('curso_tutelado_tutelado_id');

        $colegioData = $tenantTutelado->run(function () use ($colegio, $sharedCursoIds, $sharedPorCurso): array {
            $colegio = Instituicao::findOrFail($colegio);
            $cursos = InstituicaoCurso::where('instituicao_id', $colegio->id)
                ->whereHas('cursoTutelado', fn ($query) => $query->whereIn('id', $sharedCursoIds))
                ->with(['curso:id,nome', 'cursoTutelado:id,instituicao_curso_id,curso_tutelado_shared_id', 'cursoTutelado.cursoTuteladoShared:id,status'])
                ->get()
                ->map(fn ($ic): array => [
                    'id' => $ic->cursoTutelado->id,
                    'nome' => $ic->curso->nome,
                    'status' => $sharedPorCurso[(string) $ic->cursoTutelado->id]?->status?->value
                        ?? $sharedPorCurso[(string) $ic->cursoTutelado->id]?->status,
                    'curso_tutelado_id' => $ic->cursoTutelado->id,
                ])
                ->values();

            return [
                'id' => $colegio->id,
                'nome' => $colegio->nome,
                'cursos' => $cursos,
            ];
        });

        $cursos = new LengthAwarePaginator(
            $colegioData['cursos'],
            $colegioData['cursos']->count(),
            10,
            (int) $request->input('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('tenant/colegio/show', [
            'instituicao' => [
                'id' => $instituicao->id,
                'nome' => $instituicao->nome,
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
