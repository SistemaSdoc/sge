<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inscricao\StoreInscricaoRequest;
use App\Http\Requests\UpdateInscricaoRequest;
use App\Http\Resources\Inscricao\InscricaoResource;
use App\Http\Resources\Inscricao\InscricaoShowResource;
use App\Models\Aluno;
use App\Models\Candidato;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\User;
use App\Services\InscricaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InscricaoController extends Controller
{
    public function __construct(
        private InscricaoService $inscricaoService
    ) {
    }

    public function index()
    {
        $instituicaoId = Auth::user()?->instituicaoFiltro();

        $inscricoes = Inscricao::with([
            'candidato:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ])
            ->when(
                $instituicaoId,
                fn($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->latest()
            ->paginate(10);

        return Inertia::render('inscricoes/index', [
            'inscricoes' => InscricaoResource::collection($inscricoes),
        ]);
    }

    public function create()
    {
        $instituicoes = Instituicao::with([
            'instituicaoCursos.curso',
            'instituicaoCursos.cursoTutelado.cursoClasses.classe:id,nome',
            'instituicaoCursos.cursoTutelado.cursoClasses.turnos.turno:id,nome',
        ])->get();

        return Inertia::render('inscricoes/create', [
            'instituicoes' => $instituicoes->map(fn($inst) => [
                'id' => $inst->id,
                'nome' => $inst->nome,
                'cursos' => $inst->instituicaoCursos->map(fn($ci) => [
                    'id' => $ci->id,
                    'nome' => $ci->curso->nome,
                    'turnos' => $ci->cursoTutelado?->cursoClasses
                        ->filter(fn($c) => $c->classe?->nome === '10ª')
                        ->flatMap(fn($c) => $c->turnos->map(fn($t) => [
                            'id' => $t->id,
                            'nome' => $t->turno->nome,
                        ]))->values(),
                ])->filter(fn($ci) => $ci['turnos']->isNotEmpty())->values(),
            ])->filter(fn($inst) => $inst['cursos']->isNotEmpty())->values(),
        ]);
    }

    public function store(StoreInscricaoRequest $request)
    {
        $this->inscricaoService->criar($request->validated());

        return redirect()->route('inscricoes.index');
    }

    public function show(Inscricao $inscricao)
    {

        $inscricao->load([
            'candidato:id,nome,bi,numero_estudante,email,telefone,morada',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ]);


        return Inertia::render('inscricoes/show', [
            'inscricao' => (new InscricaoShowResource($inscricao))->resolve(),  // AAlterado, erro que estava a fazer aparecer tela preta ao acessar detalhes da inscrição
    ]);

    }

    /*public function edit(Aluno $aluno)
    {
        $aluno->load([
            'inscricao.candidato:id,nome,bi',
            'turmas' => fn($q) => $q->wherePivot('activo', true),
        ]);

        $turmasDisponiveis = Turma::where(
            'curso_classe_turno_id',
            $aluno->inscricao->curso_classe_turno_id
        )
            ->with('cursoClasseTurno.cursoClasse.classe:id,nome')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'nome' => $t->nome,
                'classe' => $t->cursoClasseTurno?->cursoClasse?->classe?->nome,
            ]);

        return Inertia::render('alunos/edit', [
            'aluno' => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome,
                'bi' => $aluno->inscricao?->candidato?->bi,
                'matricula' => $aluno->matricula,
                'turma_id' => $aluno->turmas->first()?->id,
            ],
            'turmas' => $turmasDisponiveis,
        ]);
    }*/

    public function update(UpdateInscricaoRequest $request, Inscricao $inscricao)
    {
        $inscricao->load([
            'candidato',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
        ]);

        try {
            $this->inscricaoService->atualizarNotaTeste(
                $inscricao,
                $request->validated()['nota_teste']
            );

            return redirect()->route('inscricoes.index');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar inscrição', [
                'inscricao_id' => $inscricao->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Erro interno do servidor.']);
        }
    }


    public function destroy(Inscricao $inscricao)
    {
        //
    }
}