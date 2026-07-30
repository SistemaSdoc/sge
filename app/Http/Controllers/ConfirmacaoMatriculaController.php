<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Services\ConfirmacaoMatriculaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfirmacaoMatriculaController extends Controller
{
    public function __construct(
        private readonly ConfirmacaoMatriculaService $confirmacaoMatriculaService,
    ) {}

    /**
     * Lista os alunos que precisam confirmar sua matrícula.
     */
    public function index()
    {
       $this->authorize('confirmacao-matricula.viewAny');

        $alunos = $this->confirmacaoMatriculaService->listarAlunosPorConfirmarMatricula();

        return Inertia::render('confirmacoes-matriculas/index', [
            'alunos' => $alunos,
        ]);
    }

    /**
     * Confirma a matrícula de um aluno.
     */
    public function store(Request $request, Aluno $aluno)
    {
        $this->authorize('confirmacao-matricula.confirmar');

        $this->confirmacaoMatriculaService->confirmarMatricula($aluno);

        return redirect()->route('confirmar-matriculas.index');
    }
}
