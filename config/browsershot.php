<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuração Browsershot
    |--------------------------------------------------------------------------
    |
    | Este ficheiro configura Spatie Browsershot para geração de PDF/Screenshots.
    | Funciona em conjunto com .puppeteerrc.cjs para configuração completa do Puppeteer.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Caminho do Binário Node
    |--------------------------------------------------------------------------
    |
    | Caminho para o executável Node.js. Em produção com NVM, usa o caminho completo.
    | Em instalações padrão, /usr/bin/node ou `which node` costuma funcionar.
    |
    | Exemplos:
    | - /usr/bin/node (Ubuntu/Debian padrão)
    | - /home/deploy/.nvm/versions/node/v24.15.0/bin/node (NVM)
    | - /usr/local/bin/node (instalações personalizadas)
    |
    */
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/usr/bin/node'),

    /*
    |--------------------------------------------------------------------------
    | Caminho do Executável Chrome/Chromium
    |--------------------------------------------------------------------------
    |
    | Caminho para o executável Chrome ou Chromium. É usado por Browsershot
    | e também é referenciado em .puppeteerrc.cjs para o Puppeteer.
    |
    | Caminhos recomendados:
    | - /opt/google/chrome/chrome (Chrome oficial do Google)
    | - /usr/bin/chromium-browser (Chromium do apt)
    | - /Applications/Google Chrome.app/Contents/MacOS/Google Chrome (macOS)
    |
    */
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/opt/google/chrome/chrome'),

    /*
    |--------------------------------------------------------------------------
    | Caminho de Node Modules
    |--------------------------------------------------------------------------
    |
    | Caminho para o diretório node_modules. Normalmente é a raiz do projeto.
    | Só é necessário se o Puppeteer estiver instalado localmente (não recomendado em prod).
    |
    */
    'node_module_path' => base_path('node_modules'),

    /*
    |--------------------------------------------------------------------------
    | Diretório Temporário
    |--------------------------------------------------------------------------
    |
    | Diretório onde o Browsershot armazena ficheiros HTML temporários.
    | Deve ter permissão de escrita do utilizador do servidor web (www-data, nginx, etc).
    |
    */
    'temporary_path' => env(
        'BROWSERSHOT_TEMP_PATH',
        storage_path('framework/browsershot')
    ),

    /*
    |--------------------------------------------------------------------------
    | Diretório Cache do Puppeteer
    |--------------------------------------------------------------------------
    |
    | NÃO É USADO quando se usa Chrome do sistema com .puppeteerrc.cjs.
    | Mantido aqui para referência/compatibilidade retroativa.
    | Pode ser removido se estiver sempre a usar o Chrome do sistema.
    |
    */
    'puppeteer_cache_path' => env(
        'PUPPETEER_CACHE_DIR',
        storage_path('framework/puppeteer')
    ),

    /*
    |--------------------------------------------------------------------------
    | Timeout do Browsershot (ms)
    |--------------------------------------------------------------------------
    |
    | Tempo máximo que o Puppeteer espera pelo Chrome responder.
    | Aumenta em servidores lentos ou PDFs complexos.
    |
    */
    'timeout' => env('BROWSERSHOT_TIMEOUT', 60000),

    /*
    |--------------------------------------------------------------------------
    | Ativar Sandbox
    |--------------------------------------------------------------------------
    |
    | Define como false para desativar o sandbox do Chrome (necessário em algumas configurações de produção).
    | Desativa apenas se necessário para o teu ambiente.
    |
    */
    'sandbox' => env('BROWSERSHOT_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Modo Headless
    |--------------------------------------------------------------------------
    |
    | Modo de renderização do Chrome. 'new' é a implementação headless moderna.
    | Usa 'true' se encontrares problemas de compatibilidade em sistemas antigos.
    |
    | Opções: 'new' (recomendado), 'true', 'false'
    |
    */
    'headless' => env('BROWSERSHOT_HEADLESS', 'new'),
    'include_path' => env('BROWSERSHOT_INCLUDE_PATH', '$PATH'),

    /*
    |--------------------------------------------------------------------------
    | Argumentos Adicionais do Chromium
    |--------------------------------------------------------------------------
    |
    | Flags extras para passar ao Chrome. Flags comuns para produção:
    | - disable-dev-shm-usage: Contorno para sistemas com /dev/shm limitado
    | - disable-gpu: Desativa aceleração GPU (recomendado para servidores)
    |
    */
    'chromium_args' => [
        'disable-dev-shm-usage',
        'disable-gpu',
        'no-sandbox' => env('BROWSERSHOT_SANDBOX', false),
    ],
];
