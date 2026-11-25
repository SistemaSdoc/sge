<?php

use App\Models\Central\DeclaracaoEstagio;

it('calculates the evaluation from the rounded final grade', function (float $nota, string $avaliacao): void {
    $declaracao = new DeclaracaoEstagio(['nota' => $nota]);

    expect($declaracao->desempenho)->toBe($avaliacao);
})->with([
    'insufficient below the threshold' => [9.4, 'Insuficiente'],
    'rounds up to sufficient' => [9.5, 'Suficiente'],
    'rounds up to good' => [13.5, 'Bom'],
    'rounds up to very good' => [16.5, 'Muito bom'],
    'rounds up to excellent' => [18.5, 'Excelente'],
]);
