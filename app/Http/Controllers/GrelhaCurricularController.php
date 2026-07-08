<?php

namespace App\Http\Controllers;

use App\Services\GrelhaCurricularService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GrelhaCurricularController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('grelha-curricular.viewAny');

        $aluno = Auth::user()->aluno;

        return Inertia::render('aluno/grelha-curricular/index', [
            'grelhaCurricular' => (new GrelhaCurricularService)->gerarGrelhaCurricular($aluno),
        ]);
    }
}
