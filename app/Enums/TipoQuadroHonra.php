<?php

namespace App\Enums;

/** Tipos de classificação que podem ser gerados para um ano lectivo. */
enum TipoQuadroHonra: string
{
    case GERAL = 'geral';
    case CURSO = 'curso';
}
