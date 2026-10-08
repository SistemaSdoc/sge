# Plano de refatoração das decisões de tutela nas notificações

## Objectivo

Reduzir a responsabilidade de `NotificacaoController::decidirTutela()` sem alterar os fluxos funcionais, estados, mensagens ou URLs actuais. Este documento regista a proposta e a implementação aplicada.

## Estado da implementação

A extracção foi aplicada: `DecidirTutela` coordena o fluxo, enquanto `DecidirConversaoTutelaPropria`, `DecidirTrocaTutela` e `DecidirSolicitacaoTutela` contêm as regras específicas. O controller mantém a validação do utilizador autenticado, o redirect e o toast. As operações que mudam para outro tenant restauram o contexto original em `finally`.

Os 3 testes de `NotificacaoControllerTest.php`, Pint, lint PHP e a listagem de rotas passaram. Os cenários de decisão em `CrossTenantIsolationTest.php` não puderam ser executados: o ficheiro tem um erro de sintaxe pré-existente fora desses cenários. O ficheiro de teste não foi alterado; a cobertura de conversão e restauração após falha continua pendente.
## Estado actual

O método privado `decidirTutela()` concentra:

- carregamento e validação da notificação do utilizador;
- resolução da ligação e do registo central `CursoTuteladoShared`;
- três fluxos diferentes: conversão para tutela própria, troca de tutela e solicitação inicial;
- validação de permissões e estados;
- alterações em bases tenant e central;
- envio de notificações para outros utilizadores;
- marcação como lida;
- decisão de redirects e mensagens toast.

As ramificações não são apenas variações de uma mesma operação. Cada uma tem transições, validações e efeitos laterais próprios, o que torna difícil rever e testar o método como unidade.

## Abordagem proposta

Manter os dois métodos públicos do controller (`aprovarTutela` e `rejeitarTutela`) como adaptadores HTTP. Eles passam o utilizador tenant autenticado, o identificador da notificação e o `TutelaStatus` para uma action coordenadora.

Criar uma action coordenadora `DecidirTutela` que:

1. encontre a notificação através da relação do utilizador autenticado;
2. resolva o registo `CursoTuteladoShared` na ligação central;
3. valide os tipos suportados;
4. encaminhe para uma action específica pelo tipo da notificação;
5. devolva um resultado pequeno com o tipo e a mensagem do toast.

Separar as regras de negócio em três actions:

- `DecidirConversaoTutelaPropria`: conversão de tutela externa para própria;
- `DecidirTrocaTutela`: aprovação/rejeição da troca pela instituição anterior;
- `DecidirSolicitacaoTutela`: decisão da solicitação inicial, incluindo a troca final quando indicada nos dados.

Cada action recebe dependências por construtor e dados tipados, por exemplo `DatabaseNotification`, `CursoTuteladoShared` e `TutelaStatus`. Evitar `app()` dentro das actions. A action coordenadora pode encaminhar as dependências comuns para as actions específicas ou recebê-las por injecção, de acordo com os construtores que reduzam melhor o acoplamento.

O controller continua responsável pela resposta HTTP: redirecciona para a página da notificação e converte o resultado da action em flash `toast`. Assim, as actions não dependem de `Redirect` nem de `Request`.

## Resultado da decisão

Usar inicialmente um array documentado com PHPDoc, por exemplo `array{type: string, message: string}`, contendo apenas os dados necessários ao toast. Não introduzir um DTO enquanto esse resultado for usado apenas por este fluxo. Se outros consumidores aparecerem, extrair um objecto de resultado nessa altura.

## Isolamento e contexto tenant

As branches de conversão e troca final inicializam outro tenant para alterar dados. A restauração do tenant original deve ocorrer em `finally`, inclusive quando uma consulta ou serviço falha:

```php
$tenantOriginalId = (string) tenancy()->tenant->getTenantKey();

try {
    tenancy()->initialize($tenantDestinoId);
    // Operação limitada aos dados do tenant destino.
} finally {
    tenancy()->initialize($tenantOriginalId);
}
```

Cada decisão deve continuar a obter notificações pela relação do utilizador do guard `tenant`. A resolução de `CursoTuteladoShared` permanece explicitamente na ligação central. Não aceitar um identificador de notificação sem validar que pertence ao utilizador autenticado.

As operações envolvem mais de uma base de dados; uma transacção local não torna uma operação cross-database atomicamente reversível. Esta refatoração deve preservar a ordem actual dos efeitos e documentar falhas parciais. Um padrão outbox/saga só deve ser considerado numa mudança funcional separada, caso seja necessário garantir recuperação automática entre bases.

## Regras que têm de permanecer

- Apenas os tipos `solicitacao_tutela`, `troca_tutela` e `conversao_tutela_propria` são decidíveis por este fluxo.
- Conversão: o tenant que iniciou a conversão é validado; o estado partilhado deve ser `ACTIVO`; o resultado é aprovado ou rejeitado.
- Troca: o tenant anterior é validado; o estado deve ser `PENDENTE_TROCA`; aprovação deixa a troca pendente para a nova instituição e rejeição restaura a tutela anterior encerrada, quando aplicável.
- Solicitação inicial: o tenant tutor é validado e o estado deve ser `PENDENTE`; a troca final aprovada actualiza o curso no tenant tutelado.
- As notificações de resultado, marcação como lida, textos dos toasts e redirect para `tenant.dashboard.notificacoes.show` mantêm o comportamento actual.

## Testes previstos

Adicionar testes de feature separados por comportamento, usando factories/helpers existentes:

1. conversão aprovada e rejeitada, incluindo acesso negado quando o tenant não é o tutor anterior;
2. troca aprovada e rejeitada, incluindo rejeição com restauração da tutela anterior encerrada;
3. solicitação inicial aprovada/rejeitada e cenário de troca final;
4. notificação pertencente a outro utilizador resulta em 404;
5. estado incompatível resulta em 422;
6. falha durante operação noutro tenant restaura o tenant original.

Além dos testes, manter assertions para redirect e toast, e executar Pint e o conjunto focado de feature tests após cada etapa.

## Sequência de implementação

1. Criar testes que caracterizem o comportamento actual de cada branch.
2. Extrair o resultado HTTP (redirect/toast) para os métodos públicos do controller.
3. Criar a action coordenadora mantendo inicialmente o fluxo sem reorganizar as regras.
4. Extrair uma branch de cada vez para as três actions específicas e executar os mesmos testes após cada extração.
5. Acrescentar restauração de contexto com `try/finally` e testar o cenário de falha.
6. Remover o método privado antigo e dependências/imports que deixarem de ser usados.

## Critérios de conclusão

- O controller não contém as regras específicas das três decisões.
- As actions recebem dados tipados e não retornam redirects.
- Os testes cobrem os resultados, bloqueios de acesso e restauração do contexto tenant.
- URLs, nomes de rotas, estados, notificações e mensagens mantêm-se compatíveis.
- O código fica formatado pelo Pint e os testes focados passam.
