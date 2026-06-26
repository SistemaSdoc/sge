<?php

namespace App\Http\Controllers;

use App\Http\Requests\BancaJuriPap\StoreRequest;
use App\Models\BancaJuriPap;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\GrupoPap;
use App\Models\Instituicao;
use App\Models\Professor;
use App\Models\Turma;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\BancaJuriPap\UpdateRequest;

class BancaJuriPapController extends Controller
{
    /**
     * Mostra o formulário para adicionar um novo integrante da banca de júri a um grupo da PAP.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $juradosNaBanca = $grupoPap->jurados()->pluck('professor_id');

        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )->get()
            ->map(fn($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/banca/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'professores' => $professores,
            'funcoes' => ['Presidente', 'Vogal 1', 'Vogal 2'],
        ]);
    }

    /**
     * Adiciona um novo integrante da banca de júri a um grupo da PAP.
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
        BancaJuriPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'professor_id' => $request->professor_id,
            'funcao' => $request->funcao,
        ]);

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
     * Mostra o formulário para editar um integrante da banca de júri.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        BancaJuriPap $bancaJuriPap
    ) {
        $juradosNaBanca = $grupoPap->jurados()
            ->where('id', '!=', $bancaJuriPap->id)
            ->pluck('professor_id');

        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )->get()
            ->map(fn($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('cursos-tutelados/classes/turnos/turmas/pap/banca/edit', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'bancaJuriPap' => $bancaJuriPap->only('id', 'professor_id', 'funcao'),
            'professores' => $professores,
            'funcoes' => ['Presidente', 'Vogal 1', 'Vogal 2'],
        ]);
    }

    /**
     * Actualiza um integrante da banca de júri.
     */
    public function update(
        UpdateRequest $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        BancaJuriPap $bancaJuriPap
    ) {
        $bancaJuriPap->update($request->only(['professor_id', 'funcao']));

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
                    'type' => 'success',
                    'message' => 'Membro da banca actualizado com sucesso!',
                ]);
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
        BancaJuriPap $bancaJuriPap
    ) {
        $bancaJuriPap->delete();

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
