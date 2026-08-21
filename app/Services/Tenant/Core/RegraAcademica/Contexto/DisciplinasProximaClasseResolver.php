<?php

namespace App\Services\Tenant\Core\RegraAcademica\Contexto;

use App\Models\Tenant\CursoClasse;
use Illuminate\Support\Collection;

/**
 * Resolve as disciplinas da próxima classe do curso.
 */
class DisciplinasProximaClasseResolver
{
    /**
     * Resolve a lista de disciplinas da classe seguinte para o contexto da avaliação.
     */
    public function resolver(string $cursoTuteladoId, int $ordemClasseActual): ?Collection
    {
        $proximaCursoClasse = CursoClasse::with('turnos.classeTurnoDisciplinas')
            ->whereHas('classe', fn ($q) => $q->where('ordem', $ordemClasseActual + 1))
            ->where('curso_tutelado_id', $cursoTuteladoId)
            ->first();

        if (! $proximaCursoClasse) {
            return null;
        }

        $disciplinas = collect();

        foreach ($proximaCursoClasse->turnos as $turno) {
            foreach ($turno->classeTurnoDisciplinas as $ctd) {
                if ($ctd->disciplina_id) {
                    $disciplinas->push($ctd->disciplina_id);
                }
            }
        }

        return $disciplinas->unique()->values();
    }
}
