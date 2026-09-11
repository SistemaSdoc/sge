<?php

return [
    // Tipos de documentos base suportados pela aplicação.
    // Ao adicionar novos tipos, adicione-os aqui para serem disponibilizados
    // automaticamente em todas as views/controladores que consomem os tipos.
    // Cada entrada usa a chave interna (valor) e o rótulo que será mostrado no UI.
    // Mantém a lista pequena por defeito; o controller pode acrescentar tipos
    // dinâmicos (ex.: Certificado) consoante regras de elegibilidade.
    'types_base' => [
        [
            'value' => 'declaracao',
            'label' => 'Declaração Escolar sem Notas',
        ],
        [
            'value' => 'declaracao_com_notas',
            'label' => 'Declaração Escolar com Notas',
        ],
        [
            'value' => 'historico',
            'label' => 'Histórico Académico',
        ],
        // Nota: 'comprovativo' removido intencionalmente — não é usado.
    ],

    // Tipo do certificado (adicionado condicionalmente quando o aluno for elegível)
    'certificado' => [
        'value' => 'certificado',
        'label' => 'Certificado de Habilitações',
    ],
];
