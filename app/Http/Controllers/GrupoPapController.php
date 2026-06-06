<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\BancaJuriPap;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\CursoTuteladoProfessor;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class GrupoPapController extends Controller //implements HasMiddleware
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
            ->latest()->get();

        return response()->json($grupos->map(fn($grupo) => [
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
        ]));
    }

    public function store(Request $request, Instituicao $instituicao, CursoTutelado $cursoTutelado, CursoClasse $cursoClasse, CursoClasseTurno $cursoClasseTurno, Turma $turma)
    {
        $cursoTutelado = CursoTutelado::findOrFail($cursoTutelado->id);

        $request->validate([
            'professor_tutor_id' => 'required|exists:professores,id',
            'nome_grupo' => 'required|string|max:255',
            'tema_grupo' => 'required|string|max:255',
            'alunos' => 'required|array|min:1',
            'alunos.*' => 'exists:alunos,id',
            'estudo_caso' => 'nullable|string',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'data_defesa' => 'nullable|date',
        ]);

        // Verificar se o professor é titular do curso
        $eTitular = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTutelado->id)
            ->where('professor_id', $request->professor_tutor_id)
            ->where('tipo', 'principal')
            ->exists();

        if (!$eTitular) {
            return response()->json([
                'message' => 'O professor tutor deve ser titular do curso.',
            ], 422);
        }

        $grupo = GrupoPap::create([
            'turma_id' => $turma->id,
            'professor_tutor_id' => $request->professor_tutor_id,
            'nome_grupo' => $request->nome_grupo,
            'tema_grupo' => $request->tema_grupo,
            'estudo_caso' => $request->estudo_caso,
            'nota_final' => $request->nota_final,
            'data_defesa' => $request->data_defesa,
        ]);

        foreach ($request->alunos as $alunoId) {
            $aluno = Aluno::where('id', $alunoId)->firstOrFail();
            ElementoGrupoPap::create([
                'grupo_pap_id' => $grupo->id,
                'aluno_id' => $aluno->id,
            ]);
        }

        $classe = $turma->cursoClasseTurno?->cursoClasse?->classe?->nome;
        if ($classe !== '13ª') {
            return response()->json([
                'message' => 'Os grupos PAP só podem ser criados para turmas da 13ª classe.'
            ], 422);
        }

        return response()->json($grupo->load('professor.user:id,nome', 'turma:id,nome', 'elementos'), 201);
    }

    public function show($id)
    {
        $grupoPap = GrupoPap::with([
            'professor.user:id,nome,email',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
            'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao:id,nome',
            'elementos.aluno.inscricao.candidato:id,nome,email,telefone',
            'jurados.professor.user:id,nome,email',
        ])->findOrFail($id);

        return response()->json([
            'id' => $grupoPap->id,
            'nome_grupo' => $grupoPap->nome_grupo,
            'tema_grupo' => $grupoPap->tema_grupo,
            'estudo_caso' => $grupoPap->estudo_caso,
            'status' => $grupoPap->status,
            'nota_final' => $grupoPap->nota_final,
            'data_defesa' => $grupoPap->data_defesa,
            'professor' => [
                'id' => $grupoPap->professor?->id,
                'nome' => $grupoPap->professor?->user->nome,
                'email' => $grupoPap->professor?->user->email,
            ],
            'turma' => $grupoPap->turma?->nome,
            'classe' => $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->classe?->nome,
            'curso' => $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
            'instituicao' => $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao?->nome,
            'elementos' => $grupoPap->elementos->map(fn($el) => [
                'id' => $el->id,
                'aluno_id' => $el->aluno->id,
                'nome' => $el->aluno?->inscricao?->candidato?->nome,
                'email' => $el->aluno?->inscricao?->candidato?->email,
                'matricula' => $el->aluno?->matricula,
                'nota_individual' => $el->nota_individual,
            ]),
            'banca' => $grupoPap->jurados->map(fn($j) => [
                'id' => $j->id,
                'professor_id' => $j->professor->id,
                'nome' => $j->professor?->user->nome,
                'email' => $j->professor?->user->email,
                'funcao' => $j->funcao,
            ]),
        ]);
    }

    public function update(Request $request, GrupoPap $grupoPap)
    {
        $request->validate([
            'nome_grupo' => 'sometimes|string|max:255',
            'tema_grupo' => 'sometimes|string|max:255',
            'estudo_caso' => 'nullable|string',
            'status' => 'sometimes|string',
            'nota_final' => 'nullable|numeric|min:0|max:20',
            'data_defesa' => 'nullable|date',
        ]);

        $grupoPap->update($request->only([
            'nome_grupo',
            'tema_grupo',
            'estudo_caso',
            'status',
            'nota_final',
            'data_defesa',
        ]));

        return response()->json(['message' => 'Grupo PAP actualizado com sucesso.']);
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

    public function destroy(GrupoPap $grupoPap)
    {
        $grupoPap->elementos()->delete();
        $grupoPap->jurados()->delete();
        $grupoPap->delete();

        return response()->json(['message' => 'Grupo PAP removido com sucesso.']);
    }

    public function adicionarElemento(Request $request, GrupoPap $grupoPap)
    {
        $request->validate([
            'aluno_id' => 'required|exists:alunos,id',
        ]);

        $aluno = Aluno::where('id', $request->aluno_id)->firstOrFail();

        $jaExiste = ElementoGrupoPap::where('grupo_pap_id', $grupoPap->id)
            ->where('aluno_id', $aluno->id)
            ->exists();

        if ($jaExiste) {
            return response()->json(['message' => 'Aluno já pertence a este grupo.'], 422);
        }

        ElementoGrupoPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'aluno_id' => $aluno->id,
        ]);

        return response()->json(['message' => 'Aluno adicionado ao grupo com sucesso.']);
    }

    public function actualizarNota(Request $request, GrupoPap $grupoPap, ElementoGrupoPap $elementoGrupoPap)
    {
        $request->validate([
            'nota_individual' => 'required|numeric|min:0|max:20',
        ]);

        $elemento = ElementoGrupoPap::where('grupo_pap_id', $grupoPap->id)
            ->where('id', $elementoGrupoPap->id)
            ->firstOrFail();

        $elemento->update(['nota_individual' => $request->nota_individual]);

        return response()->json(['message' => 'Nota actualizada com sucesso.']);
    }

    public function adicionarJurado(Request $request, GrupoPap $grupoPap)
    {
        $request->validate([
            'professor_id' => 'required|exists:professores,id',
            'funcao' => 'required|string|in:Presidente,Vogal 1,Vogal 2',
        ]);

        // Buscar o curso tutelado do grupo
        $cursoTuteladoId = $grupoPap->turma?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->id;

        // Verificar se o professor é titular do curso
        $eTitular = CursoTuteladoProfessor::where('curso_tutelado_id', $cursoTuteladoId)
            ->where('professor_id', $request->professor_id)
            ->where('tipo', 'principal')
            ->exists();

        if (!$eTitular) {
            return response()->json([
                'message' => 'O jurado deve ser professor titular do curso.',
            ], 422);
        }

        $jaExiste = BancaJuriPap::where('grupo_pap_id', $grupoPap->id)
            ->where('professor_id', $request->professor_id)
            ->exists();

        if ($jaExiste) {
            return response()->json(['message' => 'Professor já pertence à banca.'], 422);
        }

        BancaJuriPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'professor_id' => $request->professor_id,
            'funcao' => $request->funcao,
        ]);

        return response()->json(['message' => 'Jurado adicionado à banca com sucesso.']);
    }

    public function removerJurado(GrupoPap $grupoPap, $juradoId)
    {
        BancaJuriPap::where('grupo_pap_id', $grupoPap->id)
            ->where('id', $juradoId)
            ->delete();

        return response()->json(['message' => 'Jurado removido da banca com sucesso.']);
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
