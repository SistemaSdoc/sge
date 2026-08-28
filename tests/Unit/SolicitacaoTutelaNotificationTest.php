<?php

use App\Notifications\SolicitacaoTutelaNotification;

it('prepara uma notificação interna para uma solicitação de tutela', function (): void {
    $notification = new SolicitacaoTutelaNotification(
        instituicaoTutelada: 'Colégio Exemplo',
        cursoNome: 'Contabilidade',
        sharedId: 'pedido-123',
    );

    expect($notification->via((object) ['email' => null]))->toBe(['database'])
        ->and($notification->toDatabase((object) []))->toMatchArray([
            'tipo' => 'solicitacao_tutela',
            'titulo' => 'Nova solicitação de tutela',
            'curso_nome' => 'Contabilidade',
            'instituicao_tutelada' => 'Colégio Exemplo',
            'curso_tutelado_shared_id' => 'pedido-123',
        ]);
});

it('adiciona email quando o administrador tem um email válido', function (): void {
    $notification = new SolicitacaoTutelaNotification('Colégio Exemplo', 'Contabilidade', 'pedido-123');

    expect($notification->via((object) ['email' => 'admin@example.test']))
        ->toBe(['database', 'mail']);
});
