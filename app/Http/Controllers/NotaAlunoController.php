<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Services\NotaAlunoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NotaAlunoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        Gate::authorize('viewAny', Nota::class);

        $aluno = Auth::user()->aluno; // ou como resolves o aluno a partir do user

        return Inertia::render('aluno/minhas-notas/index', [
            'notas' => (new NotaAlunoService)->notas($aluno),
        ]);
    }
}
