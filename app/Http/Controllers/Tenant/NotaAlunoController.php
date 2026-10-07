<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Nota;
use App\Services\Tenant\NotaAlunoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NotaAlunoController extends Controller
{
    public function __construct(
        private NotaAlunoService $notaAlunoService,
    ) {}

    /**
     * Mostra as notas do aluno autenticado.
     */
    public function index()
    {
        $user = Auth::guard('tenant')->user();

        Gate::forUser($user)->authorize('viewAny', Nota::class);

        $aluno = $user->aluno;

        $classes = $this->notaAlunoService->classesDisponiveis($aluno);

        $classeId = request('classe_id') ?? collect($classes)->first()['id'] ?? null;

        return Inertia::render('tenant/minhas-notas/index', [
            'notas' => $this->notaAlunoService->notas($aluno, $classeId),
            'classes' => $classes,
            'classeId' => $classeId,
        ]);
    }
}
