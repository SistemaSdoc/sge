<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ElementoGrupoPap\AddElementosGrupoPap;
use App\Actions\Tenant\ElementoGrupoPap\AssignNotaElementoGrupoPap;
use App\Actions\Tenant\ElementoGrupoPap\DeleteElementoGrupoPap;
use App\Actions\Tenant\ElementoGrupoPap\PrepareElementoGrupoPapForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ElementosGrupoPap\ActualizarNotaRequest;
use App\Http\Requests\Tenant\ElementosGrupoPap\StoreRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;
use Inertia\Inertia;

class ElementoGrupoPapController extends Controller
{
    public function __construct(
        private readonly PrepareElementoGrupoPapForm $prepareElementoGrupoPapForm,
        private readonly AddElementosGrupoPap $addElementosGrupoPap,
        private readonly DeleteElementoGrupoPap $deleteElementoGrupoPap,
        private readonly AssignNotaElementoGrupoPap $assignNotaElementoGrupoPap,
    ) {}

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

        $alunos = $this->prepareElementoGrupoPapForm->handle($turma, $grupoPap);

        return Inertia::render('tenant/cursos-tutelados/classes/turnos/turmas/pap/elementos/create', [
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

        $this->addElementosGrupoPap->handle($grupoPap, $request->validated());

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

        $this->deleteElementoGrupoPap->handle($elementoGrupoPap);

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

        $this->assignNotaElementoGrupoPap->handle(
            $grupoPap,
            $elementoGrupoPap,
            $request->validated(),
        );

        return to_route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show', [
            'instituicao' => $instituicao->id,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }
}
