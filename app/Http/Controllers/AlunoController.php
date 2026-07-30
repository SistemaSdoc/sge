<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\VerificadorPropinaService;
use Inertia\Inertia;

class AlunoController extends Controller
{
    public function index(VerificadorPropinaService $verificador)
{
    Gate::authorize('viewAny', Aluno::class);

    $anoLectivoId = request('ano_lectivo_id')
        ?? AnoLectivo::activo()?->id;

    /** @var User $user */
    $user = Auth::user();

    $alunos = Aluno::whereIn('situacao', ['activo', 'finalista', 'reprovado'])
        ->doAnoLectivo($anoLectivoId)
        ->with([
            'inscricao.candidato:id,nome,bi,email,telefone',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn($q) => $q->wherePivot('activo', true)
                ->with([
                    'cursoClasseTurno.cursoClasse.classe:id,nome',
                    'anoLectivo:id,nome',
                ]),
        ])
        ->when(
            $user->hasAnyRole(['Director', 'Subdirector', 'Secretaria']),
            fn($q) => $q->whereHas(
                'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                fn($q) => $q->where('instituicao_id', $user->instituicao_id)
            )
        )
        ->when(
            $user->hasRole('Professor'),
            fn($q) => $q->whereHas(
                'turmas',
                fn($q) => $q->whereIn(
                    'turmas.id',
                    $user->professor->turmas()->pluck('turmas.id')
                )
            )
        )
        ->latest()
        ->paginate(10);

    $alunos->getCollection()->transform(function ($aluno) use ($user) {
        $aluno->can = [
            'view' => $user->can('view', $aluno),
            'update' => $user->can('update', $aluno),
            'delete' => $user->can('delete', $aluno),
        ];

        return $aluno;
    });

    return Inertia::render('alunos/index', [
        // ⬇️ MUDOU: de fn() para function() para poder ter o log dentro
        'alunos' => $alunos->through(function ($aluno) use ($verificador) {

            $status = $aluno->turmaActual()->first()
                ? ($verificador->estaEmDia($aluno) ? 'pagou' : 'atrasado')
                : 'sem_turma';

            // ⬇️ LOG TEMPORÁRIO — é aqui que ele deve estar
            \Illuminate\Support\Facades\Log::debug('[AlunoController] status calculado', [
                'aluno_id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'propina_status' => $status,
            ]);

            return [
                'id' => $aluno->id,
                'matricula' => $aluno->matricula,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'bi' => $aluno->inscricao?->candidato?->bi,
                'email' => $aluno->inscricao?->candidato?->email,
                'telefone' => $aluno->inscricao?->candidato?->telefone,
                'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'instituicao' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
                'turno' => $aluno->inscricao?->cursoClasseTurno?->turno?->nome,
                'turma' => $aluno->turmas->first()?->nome ?? 'Sem turma',
                'classe' => $aluno->turmas->first()?->cursoClasseTurno?->cursoClasse?->classe?->nome,
                'ano_lectivo' => $aluno->turmas->first()?->anoLectivo?->nome,
                'propina_status' => $status, // ⬅️ usa a variável já calculada acima
                'can' => $aluno->can,
            ];
        }),
        'anoLectivoId' => $anoLectivoId,
        'anosLectivos' => AnoLectivo::orderByDesc('data_inicio')->get(['id', 'nome']),
        'can' => [
            'create' => $user->can('create', Aluno::class),
        ],
    ]);
}

    public function show(Aluno $aluno)
    {
        Gate::authorize('view', $aluno);

        /** @var User $user */
        $user = Auth::user();

        $aluno->load([
            'inscricao.candidato:id,nome,bi,email,telefone',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn($q) => $q->wherePivot('activo', true)
                ->with([
                    'cursoClasseTurno.cursoClasse.classe:id,nome',
                    'anoLectivo:id,nome',
                ]),
        ]);

        return Inertia::render('alunos/show', [
            'aluno' => [
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
                    'ano_lectivo' => $aluno->turmas->first()?->anoLectivo?->nome,
                ],
                'can' => [
                    'update' => $user->can('update', $aluno),
                    'delete' => $user->can('delete', $aluno),
                ],
            ],
        ]);
    }

    public function edit(Aluno $aluno)
    {
        Gate::authorize('update', $aluno);

        // Ano lectivo ativo
        $anoLectivoId = AnoLectivo::activo()?->id;

        $aluno->load([
            'inscricao.candidato:id,nome,bi,email,telefone',
            'inscricao.cursoClasseTurno.turno:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'turmas' => fn($q) => $q->wherePivot('activo', true)
                ->with('cursoClasseTurno.cursoClasse.classe:id,nome'),
        ]);

        // ← NOVO: Filtrar turmas pelo ano lectivo ativo
        $turmas = Turma::where('curso_classe_turno_id', $aluno->inscricao->curso_classe_turno_id)
            ->where('ano_lectivo_id', $anoLectivoId)
            ->with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->get();

        $turmaAtual = $aluno->turmas()->wherePivot('activo', true)->first();

        return Inertia::render('alunos/edit', [
            'aluno' => [
                'id' => $aluno->id,
                'matricula' => $aluno->matricula,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'bi' => $aluno->inscricao?->candidato?->bi,
            ],
            'turmas' => $turmas->map(fn($turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome,
                'classe' => $turma->cursoClasseTurno?->cursoClasse?->classe?->nome,
            ]),
        ]);
    }

    public function update(Request $request, Aluno $aluno)
    {
        Gate::authorize('update', $aluno);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'bi' => 'required|string|max:20',
            'matricula' => 'nullable|string|max:255|unique:alunos,matricula,' . $aluno->id,
            'turma_id' => 'nullable|exists:turmas,id',
        ]);

        $aluno->update(['matricula' => $dados['matricula']]);

        $aluno->inscricao->candidato->update([
            'nome' => $dados['nome'],
            'bi' => $dados['bi'],
        ]);

        if ($dados['turma_id'] ?? null) {
            $turmaAtual = $aluno->turmas()->wherePivot('activo', true)->first();

            // Só actualiza se for uma turma diferente da actual
            if (!$turmaAtual || $turmaAtual->id !== (int) $dados['turma_id']) {
                $turma = Turma::findOrFail($dados['turma_id']);

                // Desactiva a turma anterior (se existir)
                if ($turmaAtual) {
                    $aluno->turmas()->updateExistingPivot($turmaAtual->id, [
                        'activo' => false,
                    ]);
                }

                // Activa/associa a nova turma
                $aluno->turmas()->syncWithoutDetaching([
                    $dados['turma_id'] => [
                        'activo' => true,  // ✅ Só isto
                    ],
                ]);
            }
        }

        return to_route('alunos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aluno atualizado com sucesso!',
        ]);
    }

    public function destroy(Aluno $aluno)
    {
        Gate::authorize('delete', $aluno);

        $aluno->delete();

        return to_route('alunos.index')->with('toast', [
            'type' => 'success',
            'message' => 'Aluno removido com sucesso!',
        ]);
    }
}