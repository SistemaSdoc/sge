<?php

namespace App\Services\Tenant\QuadroHonra;

class AvaliarMediaQuadroHonra
{
    /**
     * @param  list<float>  $mediasDisciplinas
     */
    public function calcular(array $mediasDisciplinas, float $mediaMinima): ?float
    {
        if ($mediasDisciplinas === []) {
            return null;
        }

        $media = array_sum($mediasDisciplinas) / count($mediasDisciplinas);

        return $media >= $mediaMinima ? $media : null;
    }
}
