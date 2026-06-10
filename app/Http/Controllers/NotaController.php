<?php

namespace App\Http\Controllers;

use App\Models\ClasseTurnoDisciplina;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\Nota;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\PautaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotaController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {}

    // ──────────────────────────────────────────────
    // LISTAR NOTAS DA DISCIPLINA
    // ──────────────────────────────────────────────

    public function index(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/index', [
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'classeId' => $cursoClasse->id,
            'turnoId' => $cursoClasseTurno->id,
            'turmaId' => $turma->id,
            'tdpId' => $tdp->id,
            'disciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
            ],
            'alunos' => $turmaAlunos->map(fn ($ta) => [
                'turma_aluno_id' => $ta->id,
                'aluno_id' => $ta->aluno->id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'notas' => $ta->notas
                    ->map(fn ($n) => $this->formatarNota($n))
                    ->keyBy('periodo'),
            ]),
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $tdp = TurmaDisciplinaProfessor::with('classeTurnoDisciplina.disciplina')
            ->where('turma_id', $turma->id)
            ->where('classe_turno_disciplina_id', $classeTurnoDisciplina->id)
            ->firstOrFail();

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/create', [
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'classeId' => $cursoClasse->id,
            'turnoId' => $cursoClasseTurno->id,
            'turmaId' => $turma->id,
            'disciplinaId' => $classeTurnoDisciplina->id,
            'data' => [
                'tdp_id' => $tdp->id,
                'disciplina' => [
                    'id' => $classeTurnoDisciplina->id,
                    'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
                    'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                ],
                'alunos' => $turmaAlunos->map(fn ($ta) => [
                    'turma_aluno_id' => $ta->id,
                    'aluno_id' => $ta->aluno->id,
                    'nome' => $ta->aluno->inscricao?->candidato?->nome,
                    'notas' => $ta->notas
                        ->map(fn ($n) => $this->formatarNota($n))
                        ->keyBy('periodo'),
                ]),
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // LANÇAR NOTAS
    // ──────────────────────────────────────────────

    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        string $classeTurnoDisciplinaId
    ) {
        $validated = $request->validate([
            'tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'periodo' => 'required|integer|in:1,2,3,4',
            'notas' => 'required|array',
            'notas.*.mac' => 'nullable|numeric|min:0|max:20',
            'notas.*.npp' => 'nullable|numeric|min:0|max:20',
            'notas.*.npt' => 'nullable|numeric|min:0|max:20',
            'notas.*.faltas' => 'nullable|integer|min:0',
            'notas.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        $this->notaService->lancarNotas(
            $validated['notas'],
            $validated['tdp_id'],
            (int) $validated['periodo'],
        );

        return back();
    }

    // ──────────────────────────────────────────────
    // LANÇAR NOTAS DE RECURSO
    // ──────────────────────────────────────────────

    public function storeRecurso(
        Request $request,
        Turma $turma,
    ): JsonResponse {

        $validated = $request->validate([
            'periodo' => 'required|integer|in:4',
            'lancamentos' => 'required|array',
            'lancamentos.*.turma_aluno_id' => 'required|exists:turma_aluno,id',
            'lancamentos.*.tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'lancamentos.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['lancamentos'] as $lancamento) {
            $this->notaService->lancarNotas(
                [
                    $lancamento['turma_aluno_id'] => [
                        'nota_recurso' => $lancamento['nota_recurso'],
                    ],
                ],
                $lancamento['tdp_id'],
                4
            );
        }

        return response()->json([
            'message' => 'Notas de recurso lançadas com sucesso.',
        ]);
    }

    // ──────────────────────────────────────────────
    // CORRIGIR NOTA
    // ──────────────────────────────────────────────

    public function update(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        TurmaDisciplinaProfessor $disciplina,
        Nota $nota
    ): JsonResponse {

        $validated = $request->validate([
            'mac' => 'nullable|numeric|min:0|max:20',
            'npp' => 'nullable|numeric|min:0|max:20',
            'npt' => 'nullable|numeric|min:0|max:20',
            'faltas' => 'nullable|integer|min:0',
        ]);

        $this->notaService->corrigirNota($nota, $validated);

        return response()->json([
            'message' => 'Nota corrigida com sucesso.',
            'nota' => $this->formatarNota($nota->fresh()),
        ]);
    }

    // ──────────────────────────────────────────────
    // PAUTA NORMAL
    // ──────────────────────────────────────────────

    public function pauta(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
        Request $request
    ): JsonResponse {

        /*abort_if(
            $turma->cursoClasseTurno?->cursoClasse?->cursoTutelado?->id !== $cursoTutelado->id,
            404
        );

        abort_if(
            $cursoTutelado->instituicao_tutora_id !== $instituicao->id,
            403
        );*/

        return response()->json(
            $this->pautaService->gerarPauta($turma, $request->query('periodo'))
        );
    }

    // ──────────────────────────────────────────────
    // PAUTA SIMPLES
    // ──────────────────────────────────────────────

    public function pautaSimples(Turma $turma, Request $request): JsonResponse
    {
        return response()->json(
            $this->pautaService->gerarPauta($turma, $request->query('periodo'))
        );
    }

    // ──────────────────────────────────────────────
    // PAUTA RECURSO
    // ──────────────────────────────────────────────

    public function pautaRecurso(

        Turma $turma,
    ): JsonResponse {

        return response()->json(
            $this->pautaService->gerarPautaRecurso($turma)
        );
    }

    // ──────────────────────────────────────────────
    // FORMATAR NOTA
    // ──────────────────────────────────────────────

    private function formatarNota(Nota $n): array
    {
        return [
            'id' => $n->id,
            'periodo' => $n->periodo,
            'mac' => $n->mac,
            'nota_prova_professor' => $n->nota_prova_professor,
            'nota_prova_trimestral' => $n->nota_prova_trimestral,
            'media_trimestral' => $n->media_trimestral,
            'media_final' => $n->media_final,
            'faltas' => $n->faltas,
            'situacao_trimestral' => $n->situacao_trimestral,
            'situacao_anual' => $n->situacao_anual,
        ];
    }
}
