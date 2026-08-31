# Plano de refatoração do serviço de tutela

> Revisão baseada nas regras locais de Laravel Best Practices, Architecture, Security, Eloquent, Database Performance, Events & Notifications, Error Handling, Validation, Routing, Testing e Style; nas convenções observadas no projecto; e nas versões instaladas Laravel 13, Stancl Tenancy 3.10 e Pest 4.

## Objetivo

Tornar o serviço responsável pela tutela externa mais claro, previsível e fácil de testar sem alterar o comportamento que já foi validado, especialmente a ordem das transações e a preservação do contexto do tenant.

O fluxo que deve continuar funcionando é:

1. Criar o curso e as classes na base do colégio.
2. Fechar a transação local do tenant.
3. Criar ou actualizar o vínculo `CursoTuteladoShared` na base central.
4. Associar o ID central ao curso local, sempre no tenant do colégio.
5. Criar a notificação no tenant tutor.
6. Devolver o fluxo ao tenant original sem executar `refresh()` na base do tutor.

## Diagnóstico do serviço actual

`CursoTuteladoSharedService` concentra pelo menos cinco responsabilidades:

- validar se uma instituição pode usar tutela externa;
- localizar e executar operações no tenant tutelado;
- criar ou actualizar o registro central partilhado;
- actualizar o curso local do tenant;
- localizar o administrador tutor e enviar a notificação.

Além disso, existem fronteiras de transação espalhadas entre o serviço e as Actions:

- `CreateCursoTutelado` controla a transação local de criação;
- `publicarEAssociar()` controla a publicação central e a associação local;
- `publicar()` abre outra transação central;
- `UpdateCursoTutelado` e `DeleteCursoTutelado` também envolvem chamadas do serviço em transações externas;
- `tornarPropria()` chama `encerrar()`, que possui a sua própria transação central.

Essa combinação foi a origem do 404: uma operação em outro tenant alterou o contexto activo antes do `refresh()` final do curso local.

### Observações adicionais das skills

- O service mistura orquestração com queries Eloquent, regras de autorização implícitas, troca de tenant e envio externo de notificações.
- `TenantService::getInstituicao()` é chamado duas vezes durante a validação; o resultado deve ser guardado uma vez.
- Os estados `pendente`, `activo`, `encerrado` e `rejeitado` são strings espalhadas pelo fluxo. Antes de extrair serviços, deve-se verificar se existe um Enum de tutela adequado; se não existir, criar um Enum apenas quando a mudança estiver coberta por testes.
- A consulta central deve continuar explicitamente ligada à conexão central e os modelos tenant devem continuar a usar a conexão tenant. Não se deve esconder esta distinção num helper genérico.
- A autorização HTTP continua pertencendo às Policies/Gates e Form Requests; a extração do service não deve transformar validação de negócio em autorização implícita.

## Decisão recomendada

Não repartir imediatamente em muitos serviços independentes. Primeiro deve-se tornar explícita a ordem das operações e definir quem é dono de cada transação. Depois, as responsabilidades podem ser extraídas para uma pasta própria.

A proposta é manter um orquestrador pequeno e extrair apenas fronteiras de domínio reais:

```text
app/Services/Tenant/Tutela/
    TutelaService.php
    TutelaValidator.php
    TutelaSharedRepository.php
    TutelaTenantService.php
    TutelaNotificationService.php
```

  ### Ajuste importante à proposta de Repository

  `TutelaSharedRepository` não deve ser criado apenas porque repositories são uma prática comum. O projecto deve primeiro confirmar se já usa uma camada de repositories de forma consistente. Se não usa, a opção preferida é um `TutelaCentralService` pequeno ou métodos privados bem nomeados dentro do orquestrador.

  O critério para extrair um repository é concreto: ele deve encapsular a conexão central, reduzir duplicação entre publicação/encerramento/remoção e tornar testável a fronteira central. Se apenas mover queries de um ficheiro para outro, aumenta a navegação sem melhorar o desenho.

### `TutelaService`

Orquestrador do fluxo de alto nível. Deve conter métodos que expressem a intenção do negócio:

- `publicarParaInstituto()`;
- `tornarTutelaPropria()`;
- `encerrarTutela()`;
- `executarNoTenantTutelado()`.

Ele coordena os serviços menores, mas não deve conter detalhes extensos de queries, payloads de notificação ou regras de URL.

O nome `CursoTuteladoSharedService` é tecnicamente correcto para a tabela, mas pouco claro para quem lê o fluxo. `TutelaService` comunica melhor a responsabilidade de negócio.

### `TutelaValidator`

Responsável apenas por validar a tutela externa e devolver os dados necessários para continuar:

- tenant tutelado actual;
- tenant tutor;
- nome da instituição tutora;
- tipo e estado permitido do tenant tutor.

A chamada actual a `TenantService::getInstituicao()` deve ser feita uma única vez e o resultado guardado numa variável. Hoje a mesma informação é consultada duas vezes.

Sugestão de método:

```php
public function validarTutor(
  Instituicao $instituicaoTutelada,
  string $tenantTutorId
): TutorTutelaData
```

O DTO deve ser uma readonly classe tipada, se a aplicação já adoptar esse padrão. Caso contrário, manter o retorno actual de string durante a primeira fase e só introduzir o DTO com testes dedicados. Não usar arrays genéricos para transportar tenant, instituição e nome.

### `TutelaSharedRepository`

Responsável pela base central e por nada no tenant local:

- encontrar o vínculo existente;
- criar o vínculo;
- actualizar o status e os dados do curso;
- apagar ou encerrar o vínculo.

A transação central deve existir neste limite, e não ficar duplicada no método que apenas orquestra a operação.

Nome alternativo, caso o projecto prefira manter o termo do modelo:

```text
CursoTuteladoSharedRepository
```

### `TutelaTenantService`

Responsável pelas alterações na base do tenant do colégio:

- associar o ID do vínculo central ao `CursoTutelado`;
- tornar a tutela própria;
- arquivar grupos PAP quando a tutela termina;
- executar uma operação dentro do tenant tutelado.

Todas as operações que precisam de trocar de tenant devem entrar explicitamente em `tenant->run(...)`. O método deve evitar transações de outra conexão dentro de uma transação já aberta pelo chamador.

O método deve receber IDs ou modelos cuja conexão esteja explícita e deve revalidar o tenant esperado quando a operação vier de uma rota ou de uma notificação. Não confiar apenas no tenant actualmente inicializado para autorizar acesso a dados de outro tenant.

### `TutelaNotificationService`

Responsável exclusivamente por:

- localizar o tenant tutor;
- obter o administrador tutor;
- construir o URL da notificação;
- enviar `SolicitacaoTutelaNotification`.

O serviço deve receber um `CursoTuteladoShared` já persistido e já confirmado. A notificação não deve ser enviada antes do commit da publicação central e da associação local.

Como a notificação pode enviar email, deve-se confirmar se o projecto quer que `SolicitacaoTutelaNotification` implemente `ShouldQueue`. Se for colocada numa fila, usar `afterCommit()` ou a configuração equivalente para impedir que o worker veja dados ainda não confirmados. O envio para a base do tutor também deve acontecer fora da transação do colégio.

## Nomes a melhorar

| Nome actual | Sugestão | Motivo |
| --- | --- | --- |
| `validarTutelaExterna` | `validarTutor` ou `validarTutelaExterna` | O nome actual é aceitável; `validarTutor` é mais directo se só validar o instituto tutor. |
| `executarNoTenantTutelado` | `executarNoTenantDoCurso` | Explicita que a operação usa a base onde vive o curso. |
| `publicar` | `criarOuActualizarVinculo` | `publicar` não revela que pode actualizar um vínculo existente. |
| `publicarEAssociar` | `publicarEAssociarCurso` | Explicita que a associação é feita no curso local. |
| `remover` | `removerVinculo` | Evita confundir a remoção do vínculo com a remoção do curso. |
| `tornarPropria` | `converterParaTutelaPropria` | Torna a mudança de estado inequívoca. |
| `encerrar` | `encerrarTutela` | Evita um verbo genérico num serviço central. |
| `notificarSolicitacao` | `notificarNovaSolicitacao` | Explicita o evento que está a ser comunicado. |
| `$shared` | `$vinculo` ou `$tutelaShared` | `shared` é curto, mas obriga o leitor a conhecer o nome técnico da tabela. |
| `$curso` | `$cursoBase` | Diferencia o curso base do `CursoTutelado`. |

Os renomes devem ser feitos em conjunto com os testes e as Actions. Não é recomendável alterar nomes e estrutura no mesmo passo em que se altera a lógica de transações.

## Regras de desenho e segurança

### Conexões e atomicidade

Laravel não fornece uma transação atómica entre as bases central e tenant através de duas closures independentes. Portanto, o desenho deve assumir que são commits separados e lidar com falhas parciais de forma explícita.

O fluxo deve:

- concluir a transação local antes de iniciar a publicação central;
- usar `DB::connection($centralConnection)` somente para modelos centrais;
- reentrar no tenant correcto com `tenant->run(...)` antes de guardar modelos tenant;
- nunca chamar `refresh()` de um modelo tenant depois de trocar para o tenant tutor sem reentrar no tenant original;
- definir uma estratégia para o caso em que a publicação central funciona e a associação local falha, como log estruturado, retry idempotente ou comando de reconciliação.

### Autorização e isolamento

- Manter `Gate::authorize()` e Policies nos controllers/actions HTTP.
- Validar que o tenant tutor pertence ao conjunto permitido antes de publicar.
- Ao aprovar ou rejeitar, confirmar no tenant e na base central que o utilizador actual é o administrador/tutor autorizado.
- Não aceitar IDs de tenant vindos do cliente sem validar o estado, o tipo da instituição e a relação com o tenant actual.
- Manter queries com binding Eloquent; não interpolar IDs em SQL.
- Evitar retornar detalhes de outros tenants em exceções ou logs.

### Estados e invariantes

Centralizar as regras de transição de estado, de preferência em métodos nomeados ou num Enum existente:

```text
pendente -> activo
pendente -> rejeitado
activo   -> encerrado
```

As transições inválidas devem produzir uma excepção de domínio ou uma resposta 422 consistente, não um 404 genérico. O 404 deve ficar reservado para recurso inexistente ou inacessível.

### Consultas e Eloquent

- Eager-load `instituicaoCurso.curso` uma vez antes de montar os atributos centrais.
- Extrair a query de procura do vínculo existente para um método nomeado, evitando uma closure longa dentro da transação.
- Usar `value()` quando apenas um campo é necessário, como o nome da instituição.
- Tipar retornos e relações novas conforme a convenção actual dos modelos.
- Confirmar índices para a combinação usada na procura do vínculo: tutor, tutelado e curso tutelado.
- Não introduzir `DB::table()` para substituir Eloquent sem uma razão de performance medida.

### Logs e erros

Substituir logs de investigação por logs estruturados e curtos, com `tenant_id`, `tenant_tutor_id`, `tenant_tutelado_id`, `curso_tutelado_id` e `shared_id`, sem dados pessoais desnecessários.

Não capturar `Throwable` para esconder falhas. Se existir uma recuperação de falha parcial, ela deve registrar a excepção e relançar ou encaminhar para um mecanismo de retry. O código não deve transformar uma falha de conexão numa resposta 404.

## Transações: regra principal

A regra deve ser documentada e mantida no código:

> Uma transação deve controlar uma única conexão. Operações de outra conexão devem ocorrer fora dela e devem reentrar explicitamente no tenant correcto.

Para criação:

```text
CreateCursoTutelado
  [transação tenant do colégio]
    criar curso, instituição-curso, curso tutelado e classes
  [commit tenant]

TutelaService
  [transação central]
    criar ou actualizar CursoTuteladoShared
  [commit central]

TutelaTenantService
  tenant do colégio -> associar shared_id

TutelaNotificationService
  tenant tutor -> criar notificação
```

Não se deve voltar a colocar a publicação central e a notificação dentro da transação local de `CreateCursoTutelado`.

Também deve ser revista a duplicação em `tornarPropria()` e `encerrar()`: actualmente um método abre uma transação central e chama outro método que abre outra transação central. O futuro dono da transação deve ser único.

## Plano de execução seguro

### Fase 1: caracterização

- Manter o serviço actual sem mudança funcional.
- Garantir testes para criação completa, actualização, conversão para tutela própria, encerramento e execução no tenant tutelado.
- Em cada teste, verificar explicitamente o tenant activo depois da operação.
- Confirmar o armazenamento central, local e da notificação.
- Adicionar um teste de falha que prove que uma excepção durante a publicação não deixa o curso local em estado parcialmente associado.
- Adicionar um teste que confirme que o tenant activo é restaurado depois de `tenantTutor->run(...)`.

### Fase 2: corrigir fronteiras no serviço actual

- Remover transações centrais duplicadas quando uma operação já está dentro de outra transação central.
- Manter a criação local separada da publicação central.
- Substituir queries longas por métodos privados nomeados, sem mudar ainda os nomes públicos.
- Guardar resultados reutilizados, como a instituição tutelada e o tenant central.
- Dar nomes aos blocos de decisão através de métodos privados pequenos, mantendo os métodos públicos durante a migração.
- Remover comentários temporários, logs de debug e qualquer código de substituição deixado durante a investigação.

Esta é a fase com menor risco e deve ser feita antes de mover ficheiros.

### Fase 3: extrair a notificação

- Criar `TutelaNotificationService`.
- Mover para ele `notificarSolicitacao()` e `tenantNotificationUrl()`.
- Injectar o novo serviço no orquestrador.
- Reutilizar os testes existentes de notificação e adicionar um teste que confirme que ela é criada depois do vínculo persistir.
- Usar `Notification::fake()` apenas nos testes que isolam o envio; manter pelo menos um teste de integração que verifique a notificação na base do tenant tutor.
- Se a notificação for enfileirada, testar o comportamento after-commit e a fila escolhida.

### Fase 4: extrair validação e persistência central

- Criar `TutelaValidator`.
- Criar `TutelaSharedRepository`.
- Manter inicialmente métodos delegadores no serviço antigo para reduzir o risco de regressão.
- Migrar as Actions uma por vez.
- Evitar introduzir interfaces ou repositories sem uma segunda implementação ou uma fronteira externa que justifique a abstração.

### Fase 5: renomear e remover o serviço antigo

- Renomear `CursoTuteladoSharedService` para `TutelaService` apenas depois de todos os call sites estarem cobertos.
- Actualizar imports e type hints.
- Remover métodos delegadores que já não tenham consumidores.
- Executar Pint e toda a suíte focada de tutela.
- Executar análise estática disponível e rever o diff para garantir que apenas imports, nomes e delegação mudaram.

## O que não fazer

- Não criar um serviço por cada método sem uma responsabilidade própria.
- Não colocar transações central e tenant dentro da mesma closure.
- Não fazer `refresh()` de um modelo tenant depois de executar `tenantTutor->run(...)` sem reentrar no tenant original.
- Não enviar notificações antes do commit dos dados de que elas dependem.
- Não mudar nomes, pastas e regras de negócio no mesmo commit lógico.
- Não usar arrays genéricos para transportar vários identificadores se um DTO ou objeto de resultado tornar o contrato mais claro.
- Não criar uma transação distribuída improvisada entre MySQL/SQLite ou central/tenant.
- Não usar `env()` dentro dos services; ler configurações através de `config()`.
- Não mover autorização para um service apenas para encurtar o controller.
- Não adicionar filas, eventos, repositories ou interfaces sem testar o novo contrato.
- Não resolver uma falha de persistência com `abort(404)`; distinguir inexistência, falta de autorização e falha interna.

## Critério de conclusão

A refatoração só deve ser considerada concluída quando:

- o fluxo completo de criação continuar a passar;
- o tenant activo depois da criação continuar a ser o colégio;
- o vínculo central tiver o tenant tutor e tutelado correctos;
- o `curso_tutelado_shared_id` estiver gravado no tenant do colégio;
- a notificação estiver gravada no tenant tutor;
- os fluxos de actualizar, encerrar, converter para própria e remover continuarem cobertos;
- não houver transações cruzadas ou aninhadas entre conexões diferentes;
- os nomes dos métodos permitirem entender o fluxo sem conhecer previamente a tabela `curso_tutelado_shared`.
- falhas parciais entre commits central e tenant serem observáveis e recuperáveis;
- nenhuma operação de leitura ou escrita depender implicitamente da última conexão activada;
- autorização, validação e persistência permanecerem em fronteiras identificáveis;
- notificações que dependem de dados persistidos só serem processadas depois do commit.

## Recomendação final

A melhor abordagem é **repartir sim, mas em fases**. O primeiro passo não deve ser mover tudo para uma pasta nova. Deve ser estabilizar os limites de transação no serviço actual e consolidar os testes. Depois, a pasta `app/Services/Tenant/Tutela/` pode receber os serviços extraídos com baixo risco e mantendo `TutelaService` como ponto de entrada claro para as Actions.

Com base nas skills e nas convenções do projecto, a recomendação final fica ligeiramente mais conservadora: começar por `TutelaService`, `TutelaValidator` e `TutelaNotificationService`; extrair `TutelaSharedRepository` apenas se a repetição de queries centrais justificar a camada. O objectivo não é ter mais classes, mas fazer com que cada fronteira importante — validação, central, tenant, notificação e transação — tenha um único dono legível.
