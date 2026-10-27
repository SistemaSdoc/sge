<?php

namespace App\Exports;

/**
 * Normaliza o resultado para apresentação sem alterar o estado académico interno.
 */
final class ResultadoPautaVisivel
{
    /**
     * Deixa sem conteúdo os resultados ainda não classificados.
     */
    public static function normalizar(?string $resultado): string
    {
        if ($resultado === null || mb_strtolower(trim($resultado), 'UTF-8') === 'incompleto') {
            return '';
        }

        return $resultado;
    }
}
