<?php

use App\Models\Tenant\Pagamento;
use App\Notifications\Aluno\PagamentoRegistadoNotification;

it('formata o valor decimal e a data do pagamento na notificação', function (): void {
    $pagamento = new Pagamento;
    $pagamento->setRawAttributes([
        'valor_total' => '1250.50',
        'data_pagamento' => '2026-09-17',
        'numero_recibo' => 'REC-001',
    ]);

    $data = (new PagamentoRegistadoNotification($pagamento))->toArray((object) []);

    expect($data)
        ->toMatchArray([
            'mensagem' => 'O seu pagamento de 1.250,50 AOA foi registado com sucesso.',
            'valor_total' => '1250.50',
            'data_pagamento' => '17/09/2026',
            'numero_recibo' => 'REC-001',
        ]);
});

it('aceita pagamento sem data ou valor', function (): void {
    $pagamento = new Pagamento;

    $data = (new PagamentoRegistadoNotification($pagamento))->toArray((object) []);

    expect($data)
        ->toMatchArray([
            'mensagem' => 'O seu pagamento de 0,00 AOA foi registado com sucesso.',
            'data_pagamento' => null,
        ]);
});
