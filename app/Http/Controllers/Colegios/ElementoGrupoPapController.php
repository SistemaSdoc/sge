<?php

namespace App\Http\Controllers\Colegios;

use App\Http\Controllers\Controller;
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
