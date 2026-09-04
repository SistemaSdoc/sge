<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ElementosGrupoPap\ActualizarNotaRequest;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;

class ElementoGrupoPapController extends Controller
{
    /**
     * Mostra o formulário para adicionar um novo elemento a um grupo da PAP.
     */
    public function actualizarNota(
        ActualizarNotaRequest $request,
        string $colegio,
        string $cursoTutelado,
        string $cursoClasse,
        string $cursoClasseTurno,
        string $turma,
        string $grupoPap,
        string $elementoGrupoPap,
    ) {
        abort_unless($request->attributes->get('cross_tenant_can_update_nota') === true, 403);

        $grupo = GrupoPap::query()->whereKey($grupoPap)->whereHas(
            'turma.cursoClasseTurno.cursoClasse',
            fn ($query) => $query->where('curso_tutelado_id', $cursoTutelado),
        )->firstOrFail();
        abort_unless(
            ! is_null($grupo->data_defesa)
                && ! $grupo->data_defesa->isFuture()
                && $grupo->jurados()->exists(),
            403,
        );
        $elemento = ElementoGrupoPap::query()
            ->whereKey($elementoGrupoPap)
            ->where('grupo_pap_id', $grupo->id)
            ->firstOrFail();

        $elemento->update(['nota_individual' => $request->nota_individual]);

        $todosComNota = $grupo->elementos()
            ->whereNull('nota_individual')
            ->doesntExist();

        if ($todosComNota) {
            $grupo->update(['status' => 'concluido']);
        }

        return to_route('tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show', [
            'colegio' => $colegio,
            'cursoTutelado' => $cursoTutelado,
            'cursoClasse' => $cursoClasse,
            'cursoClasseTurno' => $cursoClasseTurno,
            'turma' => $turma,
            'grupoPap' => $grupoPap,
        ]);
    }
}
