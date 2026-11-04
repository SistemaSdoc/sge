<?php

use App\Enums\TipoQuadroHonra;
use App\Models\Central\AnoLectivo;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\QuadroHonraItem;

it('omits the course from a general quadro heading and shows the academic year on the same line', function () {
    $quadroHonra = new QuadroHonra;
    $quadroHonra->forceFill([
        'tipo' => TipoQuadroHonra::GERAL,
        'ano_lectivo_id' => 'ano-lectivo-id',
    ]);
    $quadroHonra->setRelation('anoLectivo', new AnoLectivo(['nome' => '2026/2027']));
    $quadroHonra->setRelation('geradoPor', null);
    $quadroHonra->setRelation('itens', collect([
        new QuadroHonraItem([
            'curso' => 'Técnico de Informática de Gestão',
            'posicao' => 1,
            'nome_aluno' => 'Aluno Exemplo',
            'classe' => '12ª',
            'turma' => 'A',
            'media' => 18,
        ]),
    ]));

    $html = view('pdf.quadro-honra', ['quadroHonra' => $quadroHonra])->render();

    expect($html)->toContain('Quadro Geral — Ano lectivo 2026/2027')
        ->not->toContain('Quadro Geral — Técnico de Informática de Gestão');
});
