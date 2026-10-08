<?php

namespace App\Enums;

enum PeriodoProva: string
{
    case TRIMESTRE_1     = '1º Trimestre';
    case TRIMESTRE_2     = '2º Trimestre';
    case TRIMESTRE_3     = '3º Trimestre';
    case SEMESTRE_1      = '1º Semestre';
    case SEMESTRE_2      = '2º Semestre';
    case EXAME_ESPECIAL  = 'Exame Especial';
    case RECURSOS        = 'Recursos';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}