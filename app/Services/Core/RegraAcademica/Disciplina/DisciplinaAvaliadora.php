<?php

namespace App\Services\Core\RegraAcademica\Disciplina;

use Illuminate\Support\Collection;

/**
 * Resolve a situação de uma disciplina com base na média, no recurso e na próxima classe.
 */
class DisciplinaAvaliadora
{
    /**
     * Resolve se a disciplina fica aprovada, em recurso ou com continuidade.
     */
    public function avaliar(
        string $disciplinaId,
        float $mediaFinal,
        float $notaMinima,
        bool $ehUltimaClasse,
        bool $permiteRecurso,
        ?Collection $disciplinasProximaClasse = null,
    ): array {
        if ($mediaFinal >= $notaMinima) {
            return [
                'disciplina_id' => $disciplinaId,
                'situacao' => 'aprovado',
                'continua' => null,
                'negativa' => false,
            ];
        }

        $continua = $ehUltimaClasse
            ? false
            : $this->continua($disciplinaId, $disciplinasProximaClasse);

        $situacao = match (true) {
            $ehUltimaClasse => $permiteRecurso ? 'recurso' : 'reprovado',
            $continua => 'transita_com_deficiencia',
            default => $permiteRecurso ? 'recurso' : 'reprovado',
        };

        return [
            'disciplina_id' => $disciplinaId,
            'situacao' => $situacao,
            'continua' => $continua,
            'negativa' => true,
        ];
    }

    private function continua(
        string $disciplinaId,
        ?Collection $disciplinasProximaClasse,
    ): bool {
        return $disciplinasProximaClasse?->contains($disciplinaId) ?? false;
    }
}
