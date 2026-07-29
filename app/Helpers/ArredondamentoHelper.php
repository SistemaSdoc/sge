<?php

namespace App\Helpers;

class ArredondamentoHelper
{
    /**
     * Arredonda um valor para inteiro com a regra padrão do sistema.
     *
     * @param  float|null  $value  O valor numérico a arredondar. Null mantém null.
     * @param  string  $mode  "defeito" para arredondar normalmente, "excesso" para subir para o inteiro seguinte.
     * @return float|null Valor arredondado ou null.
     */
    public static function roundToHalf(?float $value, string $mode = 'defeito'): ?float
    {
        if (is_null($value)) {
            return null;
        }

        if ($mode === 'excesso') {
            return (float) ceil($value);
        }

        return (float) round($value);
    }
}
