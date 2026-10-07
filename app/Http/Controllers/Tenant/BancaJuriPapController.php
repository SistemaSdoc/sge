<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\BancaJuriPap\CreateBancaJuriPap;
use App\Actions\Tenant\BancaJuriPap\DeleteBancaJuriPap;
use App\Actions\Tenant\BancaJuriPap\PrepareBancaJuriPapForm;
use App\Actions\Tenant\BancaJuriPap\UpdateBancaJuriPap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BancaJuriPap\StoreRequest;
use App\Http\Requests\Tenant\BancaJuriPap\UpdateRequest;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use Inertia\Inertia;

class BancaJuriPapController extends Controller
{
    public function __construct(
        private readonly PrepareBancaJuriPapForm $prepareBancaJuriPapForm,
        private readonly CreateBancaJuriPap $createBancaJuriPap,
        private readonly UpdateBancaJuriPap $updateBancaJuriPap,
        private readonly DeleteBancaJuriPap $deleteBancaJuriPap,
    ) {}

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

        $formData = $this->prepareBancaJuriPapForm->handle(
            $turma,
            $cursoTutelado,
            $grupoPap
        );

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/banca/create', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            ...$formData,
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

        $this->createBancaJuriPap->handle($grupoPap, $request->validated());

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

        $formData = $this->prepareBancaJuriPapForm->handle(
            $turma,
            $cursoTutelado,
            $grupoPap,
            $bancaJuriPap
        );

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/banca/edit', [
            'instituicao' => $instituicao->only('id'),
            'cursoTutelado' => $cursoTutelado->only('id'),
            'cursoClasse' => $cursoClasse->only('id'),
            'cursoClasseTurno' => $cursoClasseTurno->only('id'),
            'turma' => $turma->only('id'),
            'grupoPap' => $grupoPap->only('id', 'nome_grupo'),
            'bancaJuriPap' => $bancaJuriPap->only('id', 'professor_id', 'funcao'),
            ...$formData,
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

        $this->updateBancaJuriPap->handle($bancaJuriPap, $request->validated());

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

        $this->deleteBancaJuriPap->handle($bancaJuriPap);

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
