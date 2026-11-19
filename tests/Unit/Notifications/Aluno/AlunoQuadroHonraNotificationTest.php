<?php

use App\Models\Tenant\User;
use App\Notifications\Aluno\AlunoQuadroHonraNotification;

it('notifies the student about the unified Quadro de Honra', function () {
    $notification = new AlunoQuadroHonraNotification(
        'quadro-id',
        '2026/2027',
        1,
        17.5,
        1,
    );

    expect($notification->via(new User))->toBe(['database']);

    expect($notification->toArray(new User))->toBe([
        'tipo' => 'quadro_honra',
        'titulo' => 'Felicitações! Foi distinguido(a) com a inclusão no Quadro de Honra.',
        'mensagem' => 'Informa-se que foi distinguido(a) com a inclusão no Quadro de Honra, do 1.º trimestre do ano letivo de 2026/2027, tendo alcançado a 1.ª classificação, com a média trimestral de 17,50 valores.',
        'quadro_honra_id' => 'quadro-id',
        'ano_lectivo' => '2026/2027',
        'posicao' => 1,
        'media' => 17.5,
        'periodo' => 1,
    ]);
});
