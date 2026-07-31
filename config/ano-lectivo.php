<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datas oficiais do ano letivo
    |--------------------------------------------------------------------------
    |
    | Por lei, o ano letivo começa sempre a 1 de setembro e termina a 31 de
    | julho do ano seguinte. Guardamos mês e dia separados (em vez de uma
    | string fixa) para que o cálculo do ano letivo correspondente a
    | qualquer data possa reaproveitar esses valores dinamicamente.
    |
    */

    'inicio_mes' => env('ANOLETIVO_INICIO_MES', 9),
    'inicio_dia' => env('ANOLETIVO_INICIO_DIA', 1),
    'fim_mes' => env('ANOLETIVO_FIM_MES', 7),
    'fim_dia' => env('ANOLETIVO_FIM_DIA', 31),
    'fim_hora' => env('ANOLETIVO_FIM_HORA', 23),
    'fim_minuto' => env('ANOLETIVO_FIM_MINUTO', 59),

    /*
    |--------------------------------------------------------------------------
    | Antecedência de criação do próximo ano letivo
    |--------------------------------------------------------------------------
    |
    | Define, em minutos, quanto tempo antes do fim previsto do ano letivo
    | "em_curso" o próximo ano letivo deve ser criado automaticamente. Usar
    | minutos (em vez de meses) permite testar o ciclo completo em poucos
    | minutos durante o desenvolvimento, sem alterar a lógica do código —
    | só o valor no .env.
    |
    | Produção: ~2 meses (padrão abaixo). Testes: ex. 2 minutos.
    |
    */

    'antecedencia_criacao_minutos' => env('ANOLETIVO_ANTECEDENCIA_MINUTOS', 60 * 24 * 60),

    /*
    |--------------------------------------------------------------------------
    | Status inicial do próximo ano letivo
    |--------------------------------------------------------------------------
    |
    | Quando o próximo ano letivo é criado antecipadamente (antes de entrar
    | em vigor), ele já nasce com este status — permitindo que turmas,
    | inscrições e confirmações de matrícula sejam cadastradas nele mesmo
    | antes do início oficial em 1 de setembro.
    |
    */

    'status_inicial_proximo_ano' => env('ANOLETIVO_STATUS_INICIAL', 'matriculas_abertas'),

    /*
    |--------------------------------------------------------------------------
    | Frequência de verificação do Scheduler
    |--------------------------------------------------------------------------
    |
    | Define com que frequência o command de verificação (criação do
    | próximo ano letivo / virada do ano ativo) deve rodar via Scheduler
    | do Laravel. Valores aceites: métodos do Schedule (daily, hourly,
    | everyMinute, etc.).
    |
    */

    'frequencia_verificacao' => env('ANOLETIVO_FREQUENCIA', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Status disponíveis
    |--------------------------------------------------------------------------
    |
    | Lista centralizada dos status possíveis de um ano letivo, para evitar
    | duplicar o enum da migration em Form Requests, validações e no
    | próprio código de domínio.
    |
    */

    'status_disponiveis' => [
        'planejamento',
        'matriculas_abertas',
        'em_curso',
        'encerrado',
    ],
];
