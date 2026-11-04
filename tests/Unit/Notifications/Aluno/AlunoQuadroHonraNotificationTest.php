it('Congratula o aluno incluído no Quadro de Honra geral.', function () {
    $notification = new AlunoQuadroHonraNotification(
        'quadro-id',
        '2026/2027',
        'geral',
        'Todos os cursos',
        1,
        17.5,
    );

    expect($notification->via(new User))
        ->toBe(['database'])
        ->and($notification->toArray(new User))
        ->toMatchArray([
            'tipo' => 'quadro_honra',
            'titulo' => 'Felicitações! Foi distinguido(a) com a inclusão no Quadro de Honra.',
            'mensagem' => 'Informa-se que foi distinguido(a) com a inclusão no Quadro de Honra geral do ano letivo de 2026/2027, tendo alcançado a 1.ª classificação, com a média final de 17,5 valores.',
            'quadro_honra_id' => 'quadro-id',
            'ano_lectivo' => '2026/2027',
            'tipo_quadro' => 'geral',
            'posicao' => 1,
            'media' => 17.5,
        ]);
});

it('Identifica o curso na notificação de congratulação referente ao Quadro de Honra de curso.', function () {
    $notification = new AlunoQuadroHonraNotification(
        'quadro-id',
        '2026/2027',
        'curso',
        'Técnico de Informática de Gestão',
        2,
        16.8,
    );

    expect($notification->toArray(new User))
        ->toMatchArray([
            'mensagem' => 'Informa-se que foi distinguido(a) com a inclusão no Quadro de Honra do curso de Técnico de Informática de Gestão, do ano letivo de 2026/2027, tendo alcançado a 2.ª classificação, com a média final de 16,8 valores.',
            'curso' => 'Técnico de Informática de Gestão',
            'posicao' => 2,
            'media' => 16.8,
        ]);
});
