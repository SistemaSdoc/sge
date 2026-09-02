<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BancaJuriPap\StoreRequest;
use App\Http\Requests\Tenant\BancaJuriPap\UpdateRequest;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use App\Notifications\Pap\JuradoAdicionadoBancaNotification;
use Inertia\Inertia;

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
        $this->authorize('create', [BancaJuriPap::class, $grupoPap]);

        $anoLectivoId = $turma->ano_lectivo_id; // ← NOVO

        $juradosNaBanca = $grupoPap->jurados()->pluck('professor_id');

        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn ($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )->get()
            ->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/banca/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $anoLectivoId,          // ← NOVO
            'anosLectivos' => AnoLectivo::all(),      // ← NOVO
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
        $this->authorize('create', [BancaJuriPap::class, $grupoPap]);

        $banca = BancaJuriPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'professor_id' => $request->professor_id,
            'funcao' => $request->funcao,
        ]);

        // ── Notificação ───────────────────────────────────────
        $grupoPap->load('turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao');
        $jurado = $banca->professor?->user;
        if ($jurado) {
            $jurado->notify(new JuradoAdicionadoBancaNotification($grupoPap, $banca));
        }
        // ─

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
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
        $this->authorize('update', $bancaJuriPap);

        $anoLectivoId = $turma->ano_lectivo_id; // ← NOVO

        $juradosNaBanca = $grupoPap->jurados()
            ->where('id', '!=', $bancaJuriPap->id)
            ->pluck('professor_id');

        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn ($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )->get()
            ->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])->values();

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/banca/edit', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'anoLectivoId' => $anoLectivoId,          // ← NOVO
            'anosLectivos' => AnoLectivo::all(),      // ← NOVO
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
        $this->authorize('update', $bancaJuriPap);

        $bancaJuriPap->update($request->only(['professor_id', 'funcao']));

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.index', [
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
        $this->authorize('delete', $bancaJuriPap);
        $bancaJuriPap->delete();

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }
}
