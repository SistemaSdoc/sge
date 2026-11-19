<?php

use App\Models\Tenant\User;
use App\Notifications\Aluno\QuadroHonraGeradoNotification;

it('tells students when a quarterly Quadro de Honra is generated', function (): void {
    $notification = new QuadroHonraGeradoNotification(
        'quadro-id',
        '2026/2027',
        2,
    );

    expect($notification->via(new User))->toBe(['database']);

    expect($notification->toArray(new User))->toBe([
        'tipo' => 'quadro_honra_gerado',
        'titulo' => 'Novo Quadro de Honra disponível',
        'mensagem' => 'O Quadro de Honra do 2.º trimestre do ano lectivo de 2026/2027 foi gerado. Consulte a página do Quadro de Honra.',
        'quadro_honra_id' => 'quadro-id',
        'ano_lectivo' => '2026/2027',
        'periodo' => 2,
    ]);
});
