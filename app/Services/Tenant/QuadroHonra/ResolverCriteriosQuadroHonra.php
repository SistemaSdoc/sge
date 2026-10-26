<?php

namespace App\Services\Tenant\QuadroHonra;

use InvalidArgumentException;

/** Centraliza os limites configuráveis usados na seleção dos candidatos. */
class ResolverCriteriosQuadroHonra
{
    public const MEDIA_MINIMA = 14.0;

    public const MEDIA_MAXIMA = 20.0;

    public const LIMITE_ALUNOS = 10;

    public const LIMITE_MAXIMO_ALUNOS = 65535;

    /**
     * @return array{media_minima: float, limite_alunos: int}
     */
    public function padrao(): array
    {
        return $this->resolver();
    }

    /**
     * @return array{media_minima: float, limite_alunos: int}
     */
    public function resolver(?float $mediaMinima = null, ?int $limiteAlunos = null): array
    {
        $mediaMinima ??= self::MEDIA_MINIMA;
        $limiteAlunos ??= self::LIMITE_ALUNOS;

        if ($mediaMinima < self::MEDIA_MINIMA || $mediaMinima > self::MEDIA_MAXIMA) {
            throw new InvalidArgumentException('A média mínima do Quadro de Honra deve estar entre 14 e 20 valores.');
        }

        if ($limiteAlunos < 1 || $limiteAlunos > self::LIMITE_MAXIMO_ALUNOS) {
            throw new InvalidArgumentException('O limite de alunos deve estar entre 1 e 65535.');
        }

        return [
            'media_minima' => $mediaMinima,
            'limite_alunos' => $limiteAlunos,
        ];
    }
}
