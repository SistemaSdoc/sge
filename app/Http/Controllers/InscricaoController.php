<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInscricaoRequest;
use App\Models\Aluno;
use App\Models\Candidato;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\Turma;
use App\Models\User;
use App\Services\InscricaoService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InscricaoController extends Controller // implements HasMiddleware
{
    public function __construct(
        private InscricaoService $inscricaoService
    ) {}

    /*public static function middleware(): array
    {
        return [
            new Middleware('permission:inscricoes.index', only: ['index']),
            new Middleware('permission:inscricoes.show', only: ['show', '']),
            new Middleware('permission:inscricoes.create', only: ['store']),
            new Middleware('permission:inscricoes.edit', only: ['update']),
            new Middleware('permission:inscricoes.delete', only: ['destroy']),
        ];
    }*/

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        $instituicaoId = $user ? $user->instituicaoFiltro() : null;

        $inscricoes = Inscricao::with([
            'candidato:id,nome',
            'cursoClasseTurno.turno:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
        ])
            ->when(
                $instituicaoId,
                fn ($q) => $q->whereHas(
                    'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
                    fn ($q) => $q->where('instituicao_id', $instituicaoId)
                )
            )
            ->latest()->paginate(10);

        return Inertia::render('inscricoes/index', [
            'inscricoes' => $inscricoes->through(fn ($insc) => [
                'id' => $insc->id,
                'status' => $insc->status,
                'candidato' => $insc->candidato->nome,
                'curso' => $insc->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'instituicao' => $insc->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
                'turno' => $insc->cursoClasseTurno?->turno?->nome,
            ]),
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
            'instituicoes' => $instituicoes->map(fn ($inst) => [
                'id' => $inst->id,
                'nome' => $inst->nome,
                'cursos' => $inst->instituicaoCursos->map(fn ($ci) => [
                    'id' => $ci->id,
                    'nome' => $ci->curso->nome,
                    'turnos' => $ci->cursoTutelado?->cursoClasses
                        ->filter(fn ($c) => $c->classe?->nome === '10ª')
                        ->flatMap(fn ($c) => $c->turnos->map(fn ($t) => [
                            'id' => $t->id,
                            'nome' => $t->turno->nome,
                        ]))->values(),
                ])->filter(fn ($ci) => $ci['turnos']->isNotEmpty())->values(),
            ])->filter(fn ($inst) => $inst['cursos']->isNotEmpty())->values(),
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'nome' => 'required|string|max:255',
            'bi' => 'required|string|max:20',
            'numero_estudante' => 'required|string|max:20',
            'telefone' => 'nullable|max:20',
            'email' => 'required|email|max:255|unique:candidatos,email|unique:users,email',
            // 'morada' => 'nullable|string|max:255',
            'curso_classe_turno_id' => 'required|exists:curso_classe_turno,id',
        ]);

        // 1️Criar o candidato
        $candidato = Candidato::create([
            'nome' => $request->nome,
            'bi' => $request->bi,
            'numero_estudante' => $request->numero_estudante,
            'telefone' => $request->telefone,
            'email' => $request->email,
            // 'morada' => $request->morada,
        ]);

        // 2️ Criar a inscrição
        Inscricao::create([
            'candidato_id' => $candidato->id,
            'curso_classe_turno_id' => $request->curso_classe_turno_id,
            'status' => 'pendente', // ou 'ativo', dependendo da lógica de negócio
        ]);

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
            'inscricao' => [
                'id' => $inscricao->id,
                'status' => $inscricao->status,
                'created_at' => $inscricao->created_at?->format('d/m/Y'),
                'candidato' => [
                    'nome' => $inscricao->candidato?->nome,
                    'bi' => $inscricao->candidato?->bi,
                    'numero_estudante' => $inscricao->candidato?->numero_estudante,
                    'email' => $inscricao->candidato?->email,
                    'telefone' => $inscricao->candidato?->telefone,
                    'morada' => $inscricao->candidato?->morada,
                    'nota_teste' => $inscricao->nota_teste,
                ],
                'curso' => $inscricao->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'instituicao' => $inscricao->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
                'turno' => $inscricao->cursoClasseTurno?->turno?->nome,
            ],
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
            $this->inscricaoService->atualizarNotaTeste($inscricao, $request->validated()['nota_teste']);

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