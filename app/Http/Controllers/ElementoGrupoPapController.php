<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementosGrupoPap\ActualizarNotaRequest;
use App\Http\Requests\ElementosGrupoPap\StoreRequest;
use App\Models\Aluno;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\ElementoGrupoPap;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Turma;
use Inertia\Inertia;

class ElementoGrupoPapController extends Controller
{
    /**
     * Mostra o formulário para adicionar um novo elemento a um grupo da PAP.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('create', ElementoGrupoPap::class);

        $alunosEmGrupo = ElementoGrupoPap::where('grupo_pap_id', $grupoPap->id)
            ->pluck('aluno_id');

        $alunos = Aluno::with('inscricao.candidato:id,nome')
            ->whereNotIn('id', $alunosEmGrupo)
            ->whereHas(
                'turmas',
                fn ($q) => $q
                    ->where('turmas.id', $turma->id)
                    ->where('turma_aluno.activo', true)
            )->get()
            ->map(fn ($aluno) => [
                'id' => $aluno->id,
                'nome' => $aluno->inscricao?->candidato?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/elementos/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'alunos' => $alunos,
        ]);
    }

    /**
     * Adiciona um novo elemento a um grupo da PAP.
     */
    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('create', ElementoGrupoPap::class);

        $grupoPap->elementos()->createMany(
            collect($request->alunos)->map(fn ($id) => ['aluno_id' => $id])->toArray()
        );

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }

    /**
     * Mostra o formulário para editar os dados de um elemento de um grupo da PAP.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        ElementoGrupoPap $elementoGrupoPap
    ) {
        //
    }

    /**
     * Remove o elemento de um grupo da PAP.
     */
    public function destroy(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        ElementoGrupoPap $elementoGrupoPap
    ) {
        $this->authorize('delete', $elementoGrupoPap);

        $elementoGrupoPap->delete();

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }

    /**
     * Actualiza a nota de um elemento do grupo da PAP.
     */
    public function actualizarNota(
        ActualizarNotaRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        ElementoGrupoPap $elementoGrupoPap
    ) {
        $this->authorize('atualizarNota', $elementoGrupoPap);

        $elementoGrupoPap->update(['nota_individual' => $request->nota_individual]);

        $todosComNota = $grupoPap->elementos()
            ->whereNull('nota_individual')
            ->doesntExist();

        if ($todosComNota) {
            $grupoPap->update(['status' => 'concluido']);
        }

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }
}
