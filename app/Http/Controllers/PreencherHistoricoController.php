<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\CursoClasseTurno;
use App\Models\Turma;
use App\Services\PreencherHistoricoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PreencherHistoricoController extends Controller
{
    public function __construct(
        private PreencherHistoricoService $service
    ) {}

    /**
     * Mostra modal com dados iniciais.
     */
    public function show(Aluno $aluno)
    {
        $this->authorize('view', $aluno);

        $instituicaoId = auth()->user()?->instituicao_id;

        // Classes que faltam
        $classesFaltando = $this->service->obterClassesFaltando($aluno);

        if (empty($classesFaltando)) {
            return back()->with('message', 'Aluno não precisa preencher histórico.');
        }

        // Anos lectivos passados
        $anosLectivos = AnoLectivo::where('activo', false)
            ->orderBy('data_fim', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'nome' => $a->nome,
            ])
            ->toArray();

        return Inertia::render('Historico/Preencher', [
            'aluno' => [
                'id' => $aluno->id,
                'nome' => $aluno->user?->name,
                'matricula' => $aluno->matricula,
            ],
            'classesFaltando' => $classesFaltando,
            'anosLectivos' => $anosLectivos,
        ]);
    }

    public function getTurnos(Request $request)
    {
        $anoLectivoId = $request->query('ano_lectivo_id');
        $cursoClasseId = $request->query('curso_classe_id');
        $instituicaoId = auth()->user()?->instituicao_id;

        if (! $anoLectivoId || ! $cursoClasseId || ! $instituicaoId) {
            return response()->json(['error' => 'Parâmetros faltam'], 400);
        }

        try {
            $turnos = CursoClasseTurno::where('curso_classe_id', $cursoClasseId)
                ->with('turno')
                ->get()
                ->map(fn ($cct) => [
                    'id' => $cct->id,
                    'turno_id' => $cct->turno->id,
                    'turno_nome' => $cct->turno->nome,
                ])
                ->values()
                ->toArray();

            return response()->json($turnos);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTurmas(Request $request)
    {
        $anoLectivoId = $request->query('ano_lectivo_id');
        $cursoClasseTurnoId = $request->query('curso_classe_turno_id');
        $instituicaoId = auth()->user()?->instituicao_id;

        if (! $anoLectivoId || ! $cursoClasseTurnoId || ! $instituicaoId) {
            return response()->json(['error' => 'Parâmetros faltam'], 400);
        }

        try {
            $turmas = Turma::where('curso_classe_turno_id', $cursoClasseTurnoId)
                ->where('ano_lectivo_id', $anoLectivoId)
                ->orderBy('nome')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'nome' => $t->nome,
                ])
                ->toArray();

            return response()->json($turmas);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /historico/confirmar
     */
    public function confirmar(Request $request, Aluno $aluno)
    {
        $this->authorize('update', $aluno);

        $validated = $request->validate([
            'turma_id' => 'required|uuid|exists:turmas,id',
        ]);

        try {
            $instituicaoId = auth()->user()?->instituicao_id;
            $turmaAluno = $this->service->criarTurmaAlunoHistorico(
                $aluno,
                $validated['turma_id'],
                $instituicaoId
            );

            return redirect()
                ->route('pauta.editar', ['turmaAluno' => $turmaAluno->id, 'modo' => 'historico'])
                ->with('message', 'Histórico criado. Procede ao lançamento de notas.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
