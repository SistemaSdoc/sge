<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrupoPap\DefinirDataDefesaRequest;
use App\Http\Requests\GrupoPap\StoreRequest;
use App\Http\Requests\GrupoPap\UpdateRequest;
use App\Http\Resources\GrupoPap\CreateResource;
use App\Http\Resources\GrupoPap\EditResource;
use App\Http\Resources\GrupoPap\IndexResource;
use App\Http\Resources\GrupoPap\ShowResource;
use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\GrupoPap\BancaResource;
use App\Http\Resources\GrupoPap\ElementoResource;
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
        $this->authorize('viewAny', GrupoPap::class);

        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        $grupos = GrupoPap::with([
            'professor.user:id,nome',
            'turma.cursoClasseTurno.turno:id,nome',
            'turma.cursoClasseTurno.cursoClasse.classe:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome',
        ])->when(
                $instituicaoId,
                fn($q) => $q->whereHas(
                    'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )->latest()->paginate(10);

        // dd($grupos);
        return Inertia::render('pap/index', [
            'gruposPap' => IndexResource::collection($grupos),
        ]);
    }

    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma
    ) {
        $this->authorize('create', GrupoPap::class);

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        // ← AQUI: Busca IDs de alunos que já estão em grupos PAP
        $alunosEmGrupo = ElementoGrupoPap::pluck('aluno_id');

        $alunos = Aluno::whereNotIn('id', $alunosEmGrupo)  // ← Exclui quem já está em grupo
            ->whereHas('turmas', function ($q) use ($turma) {
                $q->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true);
            })->with('inscricao.candidato:id,nome')->get()->map(fn($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'form' => new CreateResource((object) [
                'professores' => $professores,
                'alunos' => $alunos,
            ]),
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
        $this->authorize('create', GrupoPap::class);

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
            collect($request->alunos)->map(fn($id) => ['aluno_id' => $id])->toArray()
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
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('view', $grupoPap);

        $grupoPap->load([
            'professor.user:id,nome,email',
        ]);

        $banca = $grupoPap->jurados()
            ->with('professor.user:id,nome,email')
            ->paginate(10, ['*'], 'page_banca');

        $elementos = $grupoPap->elementos()
            ->with('aluno.inscricao.candidato:id,nome,email', 'aluno:id,matricula,inscricao_id')
            ->paginate(10, ['*'], 'page_elementos');


        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/show', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'grupoPap' => new ShowResource($grupoPap),
            'banca' => BancaResource::collection($banca),
            'elementos' => ElementoResource::collection($elementos),
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
        $this->authorize('update', $grupoPap);

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        $alunos = $turma->alunos()
            ->where(function ($query) use ($grupoPap) {
                $query->whereDoesntHave('grupoPap')
                    ->orWhereHas('grupoPap', function ($q) use ($grupoPap) {
                        $q->where('grupo_pap.id', $grupoPap->id);
                    });
            })
            ->get()
            ->map(function ($aluno) {
                return [
                    'id' => $aluno->id,
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
                ];
            })
            ->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'form' => new EditResource((object) [
                'professores' => $professores,
                'alunos' => $alunos,
                'grupoPap' => $grupoPap,
            ]),
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
        $this->authorize('update', $grupoPap);

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
            $grupoPap->alunos()->sync($request->alunos);
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
        $this->authorize('delete', $grupoPap);
        $grupoPap->elementos()->delete();
        $grupoPap->jurados()->delete();
        $grupoPap->delete();

        return response()->json(['message' => 'Grupo PAP removido com sucesso.']);
    }

    public function definirData(
        DefinirDataDefesaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
    ) {
        $this->authorize('definirData', $grupoPap);

        $grupoPap->update($request->only(['data_defesa', 'local_defesa']));

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
                    'type' => 'success',
                    'message' => 'Data e local da defesa definidos com sucesso!',
                ]);
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
