<?php

namespace App\Enums;

/**
 * Estados possíveis de um vínculo de tutela externa.
 */
enum TutelaStatus: string
{
    case PENDENTE = 'pendente';
    case PENDENTE_TROCA = 'pendente_troca';
    case ACTIVO = 'activo';
    case REJEITADO = 'rejeitado';
    case ENCERRADO = 'encerrado';
}
