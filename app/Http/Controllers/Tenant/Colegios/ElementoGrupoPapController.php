<?php

namespace App\Http\Controllers\Tenant\Colegios;

use App\Http\Controllers\Controller;
use App\Http\Requests\ElementosGrupoPap\ActualizarNotaRequest;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Turma;

class ElementoGrupoPapController extends Controller
{
    /**
     * Mostra o formulário para adicionar um novo elemento a um grupo da PAP.
     */
    public function actualizarNota(
        ActualizarNotaRequest $request,
        Instituicao $instituicao,
        string $colegio,
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
            'colegio' => $colegio,
            'cursoTutelado' => $cursoTutelado->id,
            'cursoClasse' => $cursoClasse->id,
            'cursoClasseTurno' => $cursoClasseTurno->id,
            'turma' => $turma->id,
            'grupoPap' => $grupoPap->id,
        ]);
    }
}
