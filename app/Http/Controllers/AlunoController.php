<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class AlunoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:alunos.index', only: ['index']),
            new Middleware('permission:alunos.show', only: ['show', 'turmasDisponiveis']),
            new Middleware('permission:alunos.edit', only: ['update']),
            new Middleware('permission:alunos.delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        $alunos = Aluno::whereIn('situacao', ['activo', 'finalista', 'reprovado'])
            ->with([
                'inscricao.candidato:id,nome,bi,email,telefone',
                'inscricao.cursoClasseTurno.turno:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
                'turmas' => fn ($q) => $q->wherePivot('activo', true)
                    ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
            ])
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->latest()->get();

        return response()->json($alunos->map(fn ($aluno) => [
            'id' => $aluno->id,
            'matricula' => $aluno->matricula,
            'nome' => $aluno->inscricao?->candidato?->nome,
            'bi' => $aluno->inscricao?->candidato?->bi,
            'email' => $aluno->inscricao?->candidato?->email,
            'telefone' => $aluno->inscricao?->candidato?->telefone,
            'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
            'turma' => $aluno->turmas->first()?->nome,
            'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome,
        ]));
    }

    public function show(Aluno $aluno)
    {
        $aluno->load([
            'inscricao.candidato:id,nome,bi,email,telefone',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn ($q) => $q->wherePivot('activo', true)
                ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
        ]);

        return response()->json([
            'id' => $aluno->id,
            'matricula' => $aluno->matricula,
            'nome' => $aluno->inscricao?->candidato?->nome,
            'bi' => $aluno->inscricao?->candidato?->bi,
            'email' => $aluno->inscricao?->candidato?->email,
            'telefone' => $aluno->inscricao?->candidato?->telefone,
            'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
            'turma' => [
                'id' => $aluno->turmas->first()?->id,
                'nome' => $aluno->turmas->first()?->nome,
                'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome,
            ],
        ]);
    }

    public function turmasDisponiveis(Aluno $aluno)
    {
        // CORRIGIDO: Campo correto da migration
        $turmas = Turma::where(
            'curso_classe_turno_id',
            $aluno->inscricao->curso_classe_turno_id // Verificar nome do campo na inscrição
        )
            ->with('cursoClasseTurno.cursoClasse.classe:id,nome') // Relação correta
            ->get();

        return response()->json($turmas->map(fn ($t) => [
            'id' => $t->id,
            'nome' => $t->nome,
            'classe' => $t->cursoClasseTurno?->cursoClasse?->classe?->nome,
        ]));
    }

    public function update(Request $request, Aluno $aluno)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'bi' => 'required|string|max:20',
            'matricula' => 'nullable|string|max:255|unique:alunos,matricula,'.$aluno->id,
            'turma_id' => 'nullable|exists:turmas,id',
        ]);

        $aluno->update(['matricula' => $request->matricula]);

        $aluno->inscricao->candidato->update([
            'nome' => $request->nome,
            'bi' => $request->bi,
        ]);

        if ($request->turma_id) {
            // CORRIGIDO: sync sem sobrescrever outras turmas do ano
            $aluno->turmas()->syncWithoutDetaching([
                $request->turma_id => ['ano_lectivo' => date('Y')],
            ]);
        }

        return response()->json(status: 200);
    }

    public function destroy(Aluno $aluno)
    {
        $aluno->delete();

        return response()->json(status: 200);
    }

    public function grelhaCurricular()
    {
        $aluno = Auth::user()->aluno;

        if (! $aluno) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        $turmaAtual = $aluno->turmas()
            // ->wherePivot('ano_lectivo', date('Y'))
            ->wherePivot('activo', true)
            ->first();

        if (! $turmaAtual) {
            return response()->json([
                'message' => 'Aluno não tem turma atribuída no ano letivo atual',
            ], 404);
        }

        $disciplinas = $turmaAtual->cursoClasseTurno
            ->classeTurnoDisciplinas()
            ->with([
                'disciplina:id,nome,sigla',
                'turmaDisciplinaProfessores' => fn ($q) => $q
                    ->where('turma_id', $turmaAtual->id)
                    ->with('professor.user:id,nome'),
            ])
            ->get()
            ->map(function ($classeTurnoDisciplina) {
                $professor = $classeTurnoDisciplina->turmaDisciplinaProfessores->first();

                return [
                    'sigla' => $classeTurnoDisciplina->disciplina->sigla,
                    'disciplina' => $classeTurnoDisciplina->disciplina->nome,
                    'professor' => $professor?->professor?->user?->nome ?? 'Sem professor',
                ];
            });

        return response()->json([
            'data' => $disciplinas,
        ]);
    }

    public function notas()
    {
        $aluno = Auth::user()->aluno;

        if (! $aluno) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        $turmaAtual = $aluno->turmas()
            // ->wherePivot('ano_lectivo', date('Y'))
            ->wherePivot('activo', true)
            ->first();

        if (! $turmaAtual) {
            return response()->json([
                'message' => 'Aluno não tem turma atribuída no ano letivo atual',
            ], 404);
        }

        $turmaAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaAtual->id)
            ->where('activo', true)
            ->first();

        if (! $turmaAluno) {
            return response()->json([
                'message' => 'Registro de aluno na turma não encontrado',
            ], 404);
        }

        $disciplinasDaTurma = $turmaAtual->turmaDisciplinaProfessor()
            ->with(['classeTurnoDisciplina.disciplina:id,nome,sigla'])
            ->get()
            ->groupBy(fn ($tdp) => $tdp->classeTurnoDisciplina->disciplina->id)
            ->map(fn ($tdps) => $tdps->first());

        $notas = $turmaAluno->notas()
            ->with(['turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina:id,nome,sigla'])
            ->get()
            ->groupBy(fn ($nota) => $nota->turmaDisciplinaProfessor->classeTurnoDisciplina->disciplina->id);

        $disciplinas = $disciplinasDaTurma->map(function ($tdp) use ($notas) {
            $disciplina = $tdp->classeTurnoDisciplina->disciplina;
            $notasPorDisciplina = $notas->get($disciplina->id, collect());

            $trimestres = collect([1, 2, 3])->mapWithKeys(function ($periodo) use ($notasPorDisciplina) {
                $nota = $notasPorDisciplina->firstWhere('periodo', $periodo);

                return [
                    $periodo => [
                        'provas' => $nota ? [
                            $nota->mac,
                            $nota->nota_prova_professor,
                            $nota->nota_prova_trimestral,
                        ] : [null, null, null],
                        'media' => $nota?->media_trimestral,
                        'faltas' => $nota?->faltas,
                        'situacao' => $nota?->situacao_trimestral,
                    ],
                ];
            })->toArray();

            $mediaFinal = $notasPorDisciplina->firstWhere('periodo', 3)?->media_final;
            $status = $notasPorDisciplina->firstWhere('periodo', 3)?->situacao_anual;

            return [
                'id' => $disciplina->id,
                'disciplina' => $disciplina->nome,
                'sigla' => $disciplina->sigla,
                'trimestres' => $trimestres,
                'total_faltas' => $notasPorDisciplina->sum('faltas'),
                'mediaFinal' => $mediaFinal,
                'status' => $status,
            ];
        })->values();

        return response()->json([
            'data' => $disciplinas,
        ]);
    }
}
