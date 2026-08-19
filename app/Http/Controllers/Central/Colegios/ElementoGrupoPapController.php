<?php

namespace App\Http\Controller\Central\Colegios;

use App\Http\Controllers\Controller;
use App\Http\Requests\ElementosGrupoPap\ActualizarNotaRequest;
use App\Models\Central\CursoClasse;
use App\Models\Central\CursoClasseTurno;
use App\Models\Central\CursoTutelado;
use App\Models\Central\ElementoGrupoPap;
use App\Models\Central\GrupoPap;
use App\Models\Central\Instituicao;
use App\Models\Central\Turma;

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
