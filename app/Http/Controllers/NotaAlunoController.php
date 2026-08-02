<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Nota;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use App\Services\NotaAlunoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NotaAlunoController extends Controller
{
    public function __construct(
        private readonly AnoLectivoResolverService $anoLectivoResolverService,
        private NotaAlunoService $notaAlunoService,
    ) {}

    /**
     * Mostra as notas do aluno autenticado.
     */
    public function index()
    {
        Gate::authorize('viewAny', Nota::class);

        $aluno = Auth::user()->aluno;
        $classes = $this->notaAlunoService->classesDisponiveis($aluno);
        $classeId = request('classe_id') ?? collect($classes)->first()['id'] ?? null;

        return Inertia::render('aluno/minhas-notas/index', [
            'notas' => $this->notaAlunoService->notas($aluno, $classeId),
            'classes' => $classes,
            'classeId' => $classeId,
        ]);
    }
}
