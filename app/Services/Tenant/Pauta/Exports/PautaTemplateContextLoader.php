<?php

namespace App\Services\Tenant\Pauta\Exports;

use App\Models\Tenant\Turma;

/**
 * Carrega as relações comuns às exportações de pautas por template.
 */
final class PautaTemplateContextLoader
{
    /**
     * Carrega a turma e as relações usadas nos cabeçalhos e no export.
     */
    public function carregar(Turma $turma): void
    {
        $turma->loadMissing([
            'anoLectivo',
            'cursoClasseTurno.turno',
            'cursoClasseTurno.cursoClasse.classe',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.instituicao',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoTutora',
        ]);
    }
}
