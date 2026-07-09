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
use App\Services\Pauta\PautaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            'notas' => fn ($q) => $q->where('turma_disciplina_professor_id', $tdp->id),
        ])
            ->where('turma_id', $turma->id)
            ->where('situacao', 'activo')
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/disciplinas/notas/index', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'tdp' => $tdp->id,
            'disciplina' => [
                'id' => $classeTurnoDisciplina->id,
                'sigla' => $tdp->classeTurnoDisciplina->disciplina->sigla,
                'nome' => $tdp->classeTurnoDisciplina->disciplina->nome,
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
        Turma $turma,
    ) {
        $validated = $request->validate([
            'periodo' => 'required|integer|in:4',
            'lancamentos' => 'required|array',
            'lancamentos.*.turma_aluno_id' => 'required|exists:turma_aluno,id',
            'lancamentos.*.tdp_id' => 'required|exists:turma_disciplina_professor,id',
            'lancamentos.*.nota_recurso' => 'nullable|numeric|min:0|max:20',
        ]);

        foreach ($validated['lancamentos'] as $lancamento) {
            $this->notaService->lancarNotas(
                [$lancamento['turma_aluno_id'] => ['nota_recurso' => $lancamento['nota_recurso']]],
                $lancamento['tdp_id'],
                4
            );
        }

        // Recalcular resultado dos alunos afectados pelo recurso
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
