<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dias de reenvio após rejeição
    |--------------------------------------------------------------------------
    |
    | Quando uma submissão é rejeitada pelo diretor, o professor ganha
    | automaticamente uma janela pessoal para reenviar uma versão corrigida.
    |
    | Se o prazo global do concurso ainda estiver aberto, a janela de reenvio
    | será limitada pela data_limite global (não estende artificialmente).
    | Caso o prazo global já tenha fechado, a janela será now() + N dias.
    |
    */

    'dias_reenvio_rejeicao' => (int) env('PROVAS_DIAS_REENVIO_REJEICAO', 7),
];