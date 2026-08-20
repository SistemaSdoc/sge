<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\GrelhaCurricularService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GrelhaCurricularController extends Controller
{
    public function __construct(private GrelhaCurricularService $grelhaCurricularService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('grelha-curricular.viewAny');

        $aluno = Auth::user()->aluno;
        $classes = $this->grelhaCurricularService->classesDisponiveis($aluno);
        $classeId = request('classe_id') ?? collect($classes)->first()['id'] ?? null;

        return Inertia::render('tenant/aluno/grelha-curricular/index', [
            'grelhaCurricular' => $this->grelhaCurricularService->gerarGrelhaCurricular($aluno, $classeId),
            'classes' => $classes,
            'classeId' => $classeId,
        ]);
    }
}
