<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrupoPap\StoreRequest;
use App\Http\Requests\GrupoPap\UpdateRequest;
use App\Http\Resources\GrupoPap\GrupoPapShowResource;
use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GrupoPapController extends Controller // implements HasMiddleware
{
    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:pap.index', only: ['index', 'alunosDisponiveis']),
            new Middleware('permission:pap.show', only: ['show']),
            new Middleware('permission:pap.create', only: ['store', 'adicionarElemento', 'adicionarJurado']),
            new Middleware('permission:pap.edit', only: ['update', 'actualizarNota']),
            new Middleware('permission:pap.delete', only: ['destroy', 'removerJurado']),
        ];
    }*/

    public function index()
    {
        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        $grupos = GrupoPap::with([
            'professor.user:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ])
            ->when(
                $instituicaoId,
                fn($q) => $q->whereHas(
                    'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->latest()->paginate(10);

        $grupos->through(fn($grupo) => [
            'id' => $grupo->id,
            'nome_grupo' => $grupo->nome_grupo,
            'tema_grupo' => $grupo->tema_grupo,
            'status' => $grupo->status,
            'nota_final' => $grupo->nota_final,
            'data_defesa' => $grupo->data_defesa,
            'professor' => $grupo->professor->user?->nome,
            'turma' => $grupo->turma?->nome,
            'classe' => $grupo->turma?->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'curso' => $grupo->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $grupo->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'num_elementos' => $grupo->elementos->count(),
            'elementos' => $grupo->elementos->map(fn($el) => [
                'id' => $el->aluno->id,
                'nome' => $el->aluno?->inscricao?->candidato?->nome,
            ])->filter(fn($el) => $el['nome'])->values(),
        ]);

        return response()->json($grupos);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'professores' => Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
                $q->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal');
            })->with('user:id,nome')->get(),
            'alunos' => Aluno::whereHas('turmas', function ($q) use ($turma) {
                $q->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true); // aluno activo nesta turma
            })->with('inscricao.candidato:id,nome')->get()->map(fn($aluno) => [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ])->values(),
        ]);
    }

    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $grupo = GrupoPap::create([
            'turma_id' => $turma->id,
            'professor_tutor_id' => $request->professor_tutor_id,
            'nome_grupo' => $request->nome_grupo,
            'tema_grupo' => $request->tema_grupo,
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        $grupo->elementos()->createMany(
            collect($request->alunos)->map(fn ($id) => ['aluno_id' => $id])->toArray()
        );

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupo->id,
        ]);
    }

    public function show(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma, GrupoPap $grupoPap
    ) {
        $grupoPap->load([
            'professor.user:id,nome,email',
            'elementos.aluno.inscricao.candidato:id,nome,email',
            'jurados.professor.user:id,nome,email',
        ]);

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'grupoPap' => new GrupoPapShowResource($grupoPap),
        ]);
    }

    /**
     * Mostra o formulário para editar os dados de um grupo da PAP.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'professores' => Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
                $q->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal');
            })->with('user:id,nome')->get(),
            'alunos' => $turma->alunos->map(function ($aluno) {
                return [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ];
            })->values(),
            'grupoPap' => [
                'id' => $grupoPap->id,
                'nome_grupo' => $grupoPap->nome_grupo,
                'tema_grupo' => $grupoPap->tema_grupo,
                'estudo_caso' => $grupoPap->estudo_caso,
                'status' => $grupoPap->status,
                'nota_final' => $grupoPap->nota_final,
                'data_defesa' => $grupoPap->data_defesa,
                'professor_tutor_id' => $grupoPap->professor_tutor_id,
                'alunos' => $grupoPap->alunos->pluck('id'),
            ],
        ]);
    }

    public function update(
        UpdateRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $grupoPap->update($request->only([
            'nome_grupo',
            'tema_grupo',
            'estudo_caso',
            'status',
            'nota_final',
            'data_defesa',
            'professor_tutor_id',
        ]));

        if ($request->has('alunos')) {
            $alunosNovos = collect($request->alunos);
            $alunosAtuais = $grupoPap->elementos()->pluck('aluno_id');

            $grupoPap->elementos()
                ->whereNotIn('aluno_id', $alunosNovos)
                ->delete();

            $alunosParaAdicionar = $alunosNovos->diff($alunosAtuais);
            $grupoPap->elementos()->createMany(
                $alunosParaAdicionar->map(fn ($id) => ['aluno_id' => $id])->toArray()
            );
        }

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Grupo PAP actualizado com sucesso!',
        ]);
    }

    public function destroy(GrupoPap $grupoPap)
    {
        $grupoPap->elementos()->delete();
        $grupoPap->jurados()->delete();
        $grupoPap->delete();

        return response()->json(['message' => 'Grupo PAP removido com sucesso.']);
    }

    public function definirData(Request $request, GrupoPap $grupoPap)
    {
        $request->validate([
            'data_defesa' => 'required|date',
            'local_defesa' => 'required|string|max:255',
        ]);

        $grupoPap->update($request->only(['data_defesa', 'local_defesa']));

        return response()->json(['message' => 'Grupo PAP actualizado com sucesso.']);
    }

    public function alunosDisponiveis(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $alunosEmGrupo = ElementoGrupoPap::pluck('aluno_id');

        $alunos = Aluno::with('inscricao.candidato:id,nome')
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas('turmas', function ($q) use ($turma) {
                $q->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true); // aluno ativo nesta turma
            })
            ->get();

        return response()->json($alunos->map(fn($aluno) => [
            'id' => $aluno->id,
            'nome' => $aluno->inscricao?->candidato?->nome,
            'matricula' => $aluno->matricula,
        ]));
    }
}