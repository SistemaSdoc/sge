<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait DashboardHelpers
{
    /**
     * Gerar label do dia relativo (hoje, amanhã, etc)
     */
    protected function obterLabelDia(int $offset, object $horario, int $diaSemana): string
    {
        if ($offset === 0 && $this->estaAulaDecorrendo($horario)) {
            return 'A decorrer';
        }

        return match ($offset) {
            0 => 'Hoje',
            1 => 'Amanhã',
            default => $this->obterNomeDia($diaSemana),
        };
    }

    /**
     * Verificar se aula está a decorrer no momento
     */
    protected function estaAulaDecorrendo(object $horario): bool
    {
        $agora = now();
        $inicio = Carbon::parse($horario->hora_inicio)->setDate($agora->year, $agora->month, $agora->day);
        $fim = Carbon::parse($horario->hora_fim)->setDate($agora->year, $agora->month, $agora->day);

        return $agora->betweenIncluded($inicio, $fim);
    }

    /**
     * Verificar se aula ainda não terminou
     */
    protected function aulaAindaNaoTerminou(string $dia, string $horaFim): bool
    {
        $agora = now();
        $fimAula = Carbon::parse($dia.' '.$horaFim);

        return $agora->lessThanOrEqualTo($fimAula);
    }

    /**
     * Obter nome do dia da semana em português
     */
    protected function obterNomeDia(int $diaSemana): string
    {
        return match ($diaSemana) {
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'desconhecido',
        };
    }
}
