<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Http\Requests\BancaJuriPap\StoreRequest;
use App\Http\Requests\BancaJuriPap\UpdateRequest;
use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\Turma;
use Inertia\Inertia;

class BancaJuriPapController extends Controller
{
    /**
     * Mostra o formulário para adicionar um novo integrante
     * da banca de júri a um grupo da PAP.
     */
    public function create(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('create', BancaJuriPap::class);

        // Buscar o colégio tutelado
        $colegioModel = Instituicao::findOrFail($colegio);

        // Ano lectivo da turma
        $anoLectivoId = $turma->ano_lectivo_id;

        // Professores que já estão na banca
        $juradosNaBanca = $grupoPap
            ->jurados()
            ->pluck('professor_id');

        // Buscar professores vinculados ao curso tutelado
        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn ($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )
            ->get()
            ->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])
            ->values();

        return Inertia::render(
            'colegio/cursos-tutelados/classes/turnos/turmas/pap/banca/create',
            [
                'instituicao' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                ],

                'colegio' => [
                    'id' => $colegioModel->id,
                    'nome' => $colegioModel->nome,
                ],

                'cursoTutelado' => $cursoTutelado->only('id'),

                'cursoClasse' => $cursoClasse->only('id'),

                'cursoClasseTurno' => $cursoClasseTurno->only('id'),

                'turma' => $turma->only('id', 'nome'),

                'anoLectivoId' => $anoLectivoId,

                'anosLectivos' => AnoLectivo::all(),

                'grupoPap' => $grupoPap->only(
                    'id',
                    'nome_grupo'
                ),

                'professores' => $professores,

                'funcoes' => [
                    'Presidente',
                    'Vogal 1',
                    'Vogal 2',
                ],
            ]
        );
    }

    /**
     * Adiciona um novo integrante da banca de júri.
     */
    public function store(
        StoreRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap
    ) {
        $this->authorize('create', BancaJuriPap::class);

        BancaJuriPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'professor_id' => $request->professor_id,
            'funcao' => $request->funcao,
        ]);

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'colegio' => $colegio,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }

    /**
     * Mostra o formulário para editar um integrante da banca.
     */
    public function edit(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        BancaJuriPap $bancaJuriPap
    ) {
        $this->authorize('update', $bancaJuriPap);

        // Buscar o colégio tutelado
        $colegioModel = Instituicao::findOrFail($colegio);

        // Ano lectivo da turma
        $anoLectivoId = $turma->ano_lectivo_id;

        // Professores que já estão na banca,
        // excepto o professor actualmente editado
        $juradosNaBanca = $grupoPap
            ->jurados()
            ->where('id', '!=', $bancaJuriPap->id)
            ->pluck('professor_id');

        $professores = Professor::with('user:id,nome')
            ->whereNotIn('id', $juradosNaBanca)
            ->whereHas(
                'cursosTutelados',
                fn ($q) => $q
                    ->where('curso_tutelado_id', $cursoTutelado->id)
                    ->where('tipo', 'principal')
            )
            ->get()
            ->map(fn ($professor) => [
                'id' => $professor->id,
                'nome' => $professor->user?->nome ?? 'Sem nome',
            ])
            ->values();

        return Inertia::render(
            'colegio/cursos-tutelados/classes/turnos/turmas/pap/banca/edit',
            [
                'instituicao' => [
                    'id' => $instituicao->id,
                    'nome' => $instituicao->nome,
                ],

                'colegio' => [
                    'id' => $colegioModel->id,
                    'nome' => $colegioModel->nome,
                ],

                'cursoTutelado' => $cursoTutelado->only('id'),

                'cursoClasse' => $cursoClasse->only('id'),

                'cursoClasseTurno' => $cursoClasseTurno->only('id'),

                'turma' => $turma->only('id', 'nome'),

                'anoLectivoId' => $anoLectivoId,

                'anosLectivos' => AnoLectivo::all(),

                'grupoPap' => $grupoPap->only(
                    'id',
                    'nome_grupo'
                ),

                'bancaJuriPap' => $bancaJuriPap->only(
                    'id',
                    'professor_id',
                    'funcao'
                ),

                'professores' => $professores,

                'funcoes' => [
                    'Presidente',
                    'Vogal 1',
                    'Vogal 2',
                ],
            ]
        );
    }

    /**
     * Actualiza um integrante da banca de júri.
     */
    public function update(
        UpdateRequest $request,
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        BancaJuriPap $bancaJuriPap
    ) {
        $this->authorize('update', $bancaJuriPap);

        $bancaJuriPap->update(
            $request->only([
                'professor_id',
                'funcao',
            ])
        );

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'colegio' => $colegio,
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
     * Remove um integrante da banca de júri.
     */
    public function destroy(
        Instituicao $instituicao,
        string $colegio,
        CursoTutelado $cursoTutelado,
        CursoClasse $cursoClasse,
        CursoClasseTurno $cursoClasseTurno,
        Turma $turma,
        GrupoPap $grupoPap,
        BancaJuriPap $bancaJuriPap
    ) {
        $this->authorize('delete', $bancaJuriPap);

        $bancaJuriPap->delete();

        return to_route('pap.show', [
            'instituicao' => $instituicao->id,
            'colegio' => $colegio,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }
}
