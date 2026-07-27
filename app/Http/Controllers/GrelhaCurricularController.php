<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
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

        $anoLectivoId = request('ano_lectivo_id')
            ?? AnoLectivo::activo()?->id;

        return Inertia::render('aluno/grelha-curricular/index', [
            'grelhaCurricular' => (new GrelhaCurricularService)->gerarGrelhaCurricular($aluno, $anoLectivoId),
            'anoLectivoId' => $anoLectivoId,
            'anosLectivos' => AnoLectivo::all(),
        ]);
    }
}
