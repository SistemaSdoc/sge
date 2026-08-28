<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\GrupoPap\ShowResource;
use App\Http\Resources\Tenant\GrupoPap\TemaCreateResource;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrupoPapTemaController extends Controller
{
    /**
     * Show the form for creating the theme/proposal.
     */
    public function create(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        // $this->authorize('create', [GrupoPap::class, $grupoPap]);
        $this->authorize('definirTema', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        $professores = Professor::whereHas('cursosTutelados', function ($q) use ($cursoTutelado) {
            $q->where('curso_tutelado_id', $cursoTutelado->id)
                ->where('tipo', 'principal');
        })->with('user:id,nome')->get();

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/tema/create', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'grupoPap' => $grupoPap->only('id'),
            'form' => new TemaCreateResource((object) [
                'professores' => $professores,
            ]),
        ]);
    }

    /**
     * Store the newly created theme/proposal.
     */
    public function store(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('definirTema', $grupoPap);

        $validated = $request->validate([
            'tema_grupo' => 'required|string|max:255',
            'problema' => 'nullable|string|max:1000',
            'professor_tutor_id' => 'nullable|exists:professores,id',
            'objectivos' => 'nullable|string|max:1000',
            'estudo_caso' => 'nullable|string|max:1000',
        ]);

        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO,
        ]);

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,

        ])->with('toast', [
            'type' => 'success',
            'message' => 'Proposta do grupo PAP criada com sucesso!',
        ]);
    }

    /**
     * Show the form for editing the theme/proposal.
     */
    public function edit(
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('definirTema', $grupoPap);

        $anoLectivoId = $turma->ano_lectivo_id;

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/tema/edit', [
            'instituicao' => $instituicao->only('id', 'nome'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id', 'nome'),
            'anoLectivoId' => $anoLectivoId,
            'grupoPap' => new ShowResource($grupoPap),
        ]);
    }

    /**
     * Update the theme/proposal in storage.
     */
    public function update(
        Request $request,
        Instituicao $instituicao,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('definirTema', $grupoPap);

        $validated = $request->validate([
            'tema_grupo' => 'required|string|max:255',
            'problema' => 'nullable|string|max:1000',
            'objectivos' => 'nullable|string|max:1000',
            'estudo_caso' => 'nullable|string|max:1000',
        ]);

        $grupoPap->update([
            ...$validated,
            'status_aprovacao' => GrupoPap::APROVACAO_SUBMETIDO, // ← também aqui
        ]);

        return to_route('tenant.dashboard.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Proposta do grupo PAP actualizada com sucesso!',
        ]);
    }
}
