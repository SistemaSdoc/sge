<?php

namespace App\Http\Controllers\Central;

use App\Models\Central\ClasseTurnoDisciplina;
use App\Models\Central\CursoClasse;
use App\Models\Central\CursoClasseTurno;
use App\Models\Central\CursoTutelado;
use App\Models\Central\Instituicao;
use App\Models\Central\Nota;
use App\Models\Central\Turma;
use App\Models\Central\TurmaAluno;
use App\Models\Central\TurmaDisciplinaProfessor;
use App\Services\NotaService;
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NotaDisciplinaRecursoController extends Controller
{
    public function __construct(
        private readonly NotaService $notaService,
        private readonly PautaService $pautaService,
    ) {}

    /**
     * Lista as notas das provas do recurso dos alunos de uma turma ...
     */
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

        Gate::authorize('view', $tdp);

        $turmaAlunos = TurmaAluno::with([
            'aluno.inscricao.candidato:id,nome',
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id)
                ->whereIn('periodo', [3, 4]),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->filter(function ($ta) {
                $notaP3 = $ta->notas->firstWhere('periodo', 3);

                return $notaP3
                    && $notaP3->media_final !== null
                    && $notaP3->media_final >= 7
                    && $notaP3->media_final < 10;
            });

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas', [
            'tdp_id' => $tdp->id,
            'disciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
            ],
            'alunos' => $turmaAlunos->values()->map(fn ($ta) => [
                'turma_aluno_id' => $ta->id,
                'aluno_id' => $ta->aluno->id,
                'nome' => $ta->aluno->inscricao?->candidato?->nome,
                'tdp_id' => $tdp->id,
                'media_final_p3' => $ta->notas->firstWhere('periodo', 3)?->media_final,
                'nota_recurso' => $ta->notas->firstWhere('periodo', 4)?->media_final,
            ]),
        ]);
    }

    /**
     * Mostra o formulário de lançamento das notas das provas do recurso dos alunos de uma turma ...
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        Turma $turma,
    ) {
        $pauta = $this->pautaService->gerarPauta($turma, 4, 5);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/notas/recurso/create', [
            'instituicaoId' => $instituicao->id,
            'cursoId' => $cursoTutelado->id,
            'turmaId' => $turma->id,
            'turma' => $pauta['turma'],
            'disciplinas' => $pauta['disciplinas'],
            'resumo' => $pauta['resumo'],
            'alunos' => $pauta['alunos'],
        ]);
    }

    /**
     * Salva as notas das provas do recurso dos alunos de uma turma ...
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        ClasseTurnoDisciplina $classeTurnoDisciplina
    ) {
        $validated = $request->validate([
            'lancamentos' => 'required|array|min:1',
            'lancamentos.*.turma_aluno_id' => 'required|exists:turma_aluno,id',
            'lancamentos.*.tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'lancamentos.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        $tdp = TurmaDisciplinaProfessor::findOrFail($validated['lancamentos'][0]['tdp_id']);

        if (! $this->notaService->periodoPodeSerLancado($tdp->id, 4)) {
            throw ValidationException::withMessages([
                'lancamentos' => 'Primeiro lança os três trimestres anteriores para continuar.',
            ]);
        }

        foreach ($validated['lancamentos'] as $lancamento) {
            Nota::updateOrCreate(
                [
                    'turma_aluno_id' => $lancamento['turma_aluno_id'],
                    'turma_disciplina_professor_id' => $lancamento['tdp_id'],
                    'periodo' => 4,
                ],
                [
                    'media_final' => $lancamento['nota_recurso'],
                ]
            );
        }

        // Recalcular resultado dos alunos afectados
        $turmaAlunoIds = collect($validated['lancamentos'])->pluck('turma_aluno_id')->unique();

        TurmaAluno::with([
            'aluno',
            'notas',
            'turma.cursoClasseTurno.cursoClasse.classe',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado',
        ])
            ->whereIn('id', $turmaAlunoIds)
            ->each(fn ($ta) => $this->pautaService->actualizarResultadoAluno($ta));

        return back();
    }

    /**
     * Função para formatar as notas de uma aluno
     */
    private function formatarNota(Nota $nota): array
    {
        return [
            'id' => $nota->id,
            'periodo' => $nota->periodo,
            'mac' => $nota->mac,
            'nota_prova_professor' => $nota->nota_prova_professor,
            'nota_prova_trimestral' => $nota->nota_prova_trimestral,
            'media_trimestral' => $nota->media_trimestral,
            'media_final' => $nota->media_final,
            'faltas' => $nota->faltas,
            'situacao_trimestral' => $nota->situacao_trimestral,
            'situacao_anual' => $nota->situacao_anual,
        ];
    }
}
