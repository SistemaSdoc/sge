<?php

namespace App\Services\Core\RegraAcademica\Disciplina;

use Illuminate\Support\Collection;

/**
 * Entry point para avaliar uma disciplina no contexto da regra académica.
 */
class Disciplina
{
    public function __construct(
        private readonly DisciplinaAvaliadora $avaliadora,
    ) {}

    /**
     * Resolve o estado académico de uma disciplina.
     */
    public function avaliar(
        string $disciplinaId,
        float $mediaFinal,
        float $notaMinima,
        bool $ehUltimaClasse,
        bool $permiteRecurso,
        ?Collection $disciplinasProximaClasse = null,
    ): array {
        return $this->avaliadora->avaliar(
            disciplinaId: $disciplinaId,
            mediaFinal: $mediaFinal,
            notaMinima: $notaMinima,
            ehUltimaClasse: $ehUltimaClasse,
            permiteRecurso: $permiteRecurso,
            disciplinasProximaClasse: $disciplinasProximaClasse,
        );
    }
}
