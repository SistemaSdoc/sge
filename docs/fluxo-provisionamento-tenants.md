# Fluxo de Provisionamento de Tenants

## Objetivo

Garantir que um tenant não seja disponibilizado enquanto a base de dados ainda está a ser criada, migrada e populada.

O fluxo também precisa permitir que o administrador veja o motivo de uma falha e tente novamente sem criar uma segunda instituição ou utilizador.

## Como era antes

O fluxo original funcionava assim:

```text
PENDING
  -> alteração para TRIAL ou ACTIVE
  -> criação da base de dados
  -> migrations
  -> seed
  -> criação da instituição e utilizador
  -> acesso do tenant
```

Havia quatro problemas principais:

1. O status `TRIAL` ou `ACTIVE` era gravado antes de o provisionamento terminar.
2. A criação da base, migrations e seed acontecia dentro do request HTTP do administrador.
3. Se a base já existisse mas a migration de `sessions` ainda não tivesse terminado, o middleware de sessão podia lançar `QueryException`.
4. Uma falha podia deixar uma base parcialmente criada, sem estado claro nem botão de recuperação.

Além disso, `PENDING` era considerado acessível pelo enum, apesar de a regra funcional dizer que não deveria ser.

## Como está agora

O ciclo de estados passou a ser:

```text
PENDING
  -> PROVISIONING
  -> TRIAL ou ACTIVE

PROVISIONING
  -> FAILED, depois das tentativas automáticas falharem

FAILED
  -> retry manual
  -> PROVISIONING
```

### `PENDING`

Tenant criado, mas ainda não ativado pela administração.

- Não tem base de dados tenant disponível.
- Não pode entrar no sistema tenant.
- Continua visível e administrável na central.
- Apresenta a página de setup quando acessado pelo domínio tenant.

### `PROVISIONING`

Provisionamento em andamento.

- A base pode ainda não existir ou estar com migrations incompletas.
- O tenant não pode fazer login.
- O middleware apresenta `tenant-configuring`.
- O painel central consegue abrir o detalhe sem executar métricas na base incompleta.
- A página tenant faz polling HTTP e volta ao login quando a URL passa a responder normalmente.

### `TRIAL` e `ACTIVE`

Provisionamento concluído.

- A base existe.
- As migrations e seed terminaram.
- A instituição e o utilizador inicial foram criados.
- O tenant pode seguir para o login normal.

### `FAILED`

As tentativas automáticas de provisionamento terminaram sem sucesso.

- O tenant continua bloqueado.
- O erro técnico fica guardado na central.
- O administrador pode abrir uma página de falha.
- A página mostra o número de tentativas, a mensagem do erro e uma ação de retry.

## O que foi adicionado

### Enum de status

Em `app/Enums/TenantStatus.php` foram adicionados:

- `PROVISIONING`: base e ambiente em configuração.
- `FAILED`: configuração falhou depois das tentativas disponíveis.

`PENDING` e `PROVISIONING` não passam em `canAccess()`.

### Dados de acompanhamento

A migration `add_provisioning_fields_to_tenants_table` adiciona à tabela central `tenants`:

- `provisioning_target_status`: guarda se o objetivo era `trial` ou `active`.
- `provisioning_attempts`: quantidade de ativações/tentativas iniciadas.
- `provisioning_error`: última mensagem de falha.
- `provisioning_started_at`: início do provisionamento.
- `provisioning_finished_at`: fim, com sucesso ou falha.

Esses campos permitem observar o processo sem consultar a base tenant.

### Job assíncrono

`app/Jobs/ProvisionTenantJob.php` substitui o pipeline síncrono.

Ele executa:

1. criação da base de dados;
2. migrations tenant;
3. seed tenant;
4. criação idempotente da instituição e do utilizador;
5. mudança para o status final (`TRIAL` ou `ACTIVE`).

O job possui:

- `ShouldQueue`, para não bloquear o request web;
- `ShouldBeUnique`, usando o ID do tenant;
- três tentativas;
- backoff de 60, 300 e 900 segundos;
- método `failed()` para gravar `FAILED` e o erro.

O dispatch usa `afterCommit()`, para o job só ser colocado na fila depois da transação central confirmar o estado `PROVISIONING`.

A fila continua a ser a fila padrão `database`, compatível com o worker já usado por `composer run dev`.

### Lock contra ativação duplicada

`TenantService::transitionStatus()` usa `lockForUpdate()` para impedir que duas requisições leiam e alterem o mesmo tenant simultaneamente.

Também foram removidas transições silenciosas: uma transição inválida lança `LogicException` em vez de retornar sucesso falso.

### Criação idempotente

`CreateTenantInstitution` usa `firstOrCreate()` para não duplicar instituição e utilizador durante retries.

A notificação do utilizador só é enviada quando o utilizador foi criado pela primeira vez.

### Dados pendentes numa transação

A criação do tenant, domínio e `pending_tenant_data` agora acontece dentro da mesma transação central.

A notificação de tenant pendente é enviada depois do commit. Isso evita notificar uma operação que acabou revertida ou criar um tenant sem os dados necessários para ativação.

Foi criada uma migration separada para tornar `pending_tenant_data.tenant_id` único.

### Central protegida

As páginas centrais de detalhe, métricas e tabelas não tentam consultar a base tenant quando o status é `PENDING` ou `PROVISIONING`.

A central continua conseguindo:

- listar o tenant;
- mostrar o status;
- mostrar o domínio;
- abrir a página de detalhe;
- acompanhar um tenant em configuração.

### Página de falha

A página `resources/js/pages/central/tenants/provisioning-failed.jsx` mostra:

- que o tenant não foi disponibilizado;
- número de tentativas;
- mensagem técnica guardada;
- botão de voltar à lista;
- botão de tentar novamente.

### Tratamento do erro `sessions`

Durante migrations, uma requisição pode chegar ao `StartSession` antes de a tabela `sessions` existir.

O handler em `bootstrap/app.php` reconhece `QueryException` apenas quando:

- o tenancy está inicializado;
- a conexão é `tenant`;
- o status é `PROVISIONING`;
- o SQLSTATE indica tabela inexistente (`42S02` MySQL ou `42P01` PostgreSQL).

Assim, uma tabela quebrada num tenant `ACTIVE` não é mascarada como “configuração”.

## Como o polling funciona

`usePoll()` foi testado, mas não é adequado para este caso específico. Ele usa `router.reload()` e espera uma resposta Inertia normal. Como a página de configuração é uma resposta HTTP `503`, o Inertia classifica a visita como `httpException` e mantém a página atual.

A solução atual usa `fetch()` a cada cinco segundos:

- `403` ou `503`: continua na página de configuração;
- `2xx`: recarrega a página e permite chegar ao login normal;
- impede verificações sobrepostas se uma requisição demorar mais de cinco segundos.

## O que ainda falta corrigir

### 1. Retry de `FAILED` ainda não está completo

A lista de transições apresenta retry para `FAILED`, mas `transitionStatus()` ainda só trata ativação a partir de `PENDING`:

```php
['pending', 'trial'], ['pending', 'active']
```

É necessário aceitar também:

```php
['failed', 'trial'], ['failed', 'active']
```

Sem essa correção, o botão de retry pode retornar uma transição inválida.

### 2. Verificar o worker em cada ambiente

Em desenvolvimento, `composer run dev` inicia `queue:work`.

Em produção, é obrigatório configurar um worker persistente, normalmente supervisionado por Supervisor, systemd ou Horizon. Sem worker, os tenants ficam em `PROVISIONING` indefinidamente.

### 3. Testar uma falha real e um retry real

Os testes atuais cobrem principalmente o enum, transições expostas e unicidade do job. Ainda é necessário testar em ambiente controlado:

- falha durante `CreateDatabase`;
- falha durante migration;
- tabela `sessions` inexistente;
- status `FAILED` na central;
- retry após base parcialmente criada;
- ausência de duplicação de instituição/utilizador;
- duas ativações simultâneas;
- worker parado e depois reiniciado.

### 4. Rever os dados técnicos exibidos

A mensagem guardada em `provisioning_error` pode conter nomes de tabelas, host ou detalhes internos. A página deve ser restrita a administradores confiáveis. Para produção, pode ser melhor mostrar uma mensagem amigável e deixar o detalhe completo apenas no log.

### 5. Considerar limpeza de base parcial

Se a criação da base funcionar e uma migration falhar, a base fica disponível, mas incompleta. O retry atual reaproveita essa base e depende de migrations idempotentes.

Antes de produção, decidir entre:

- reaproveitar a base e continuar migrations;
- apagar a base parcial antes de tentar novamente;
- manter uma rotina de diagnóstico/reparo.

A primeira opção costuma ser mais segura quando o comando de migration suporta continuar corretamente.

## Checklist antes de produção

- [ ] Corrigir o retry de `FAILED` em `TenantService`.
- [ ] Fazer deploy das migrations centrais antes do código que usa os novos campos.
- [ ] Confirmar que `pending_tenant_data.tenant_id` não possui duplicados.
- [ ] Configurar e monitorar `php artisan queue:work` em produção.
- [ ] Confirmar que a tabela central `jobs` existe.
- [ ] Confirmar que a tabela central `failed_jobs` existe.
- [ ] Testar uma ativação normal de `PENDING` para `TRIAL`.
- [ ] Testar uma ativação normal de `PENDING` para `ACTIVE`.
- [ ] Simular migration falhando.
- [ ] Confirmar a página central de falha.
- [ ] Confirmar retry sem duplicar instituição/utilizador.
- [ ] Confirmar login tenant depois do fim das migrations.
- [ ] Não expor detalhes técnicos a utilizadores comuns.
- [ ] Rever alterações não relacionadas e artefatos gerados no `git status` antes do deploy.

## Veredito

**Ainda não levaria esta versão diretamente para produção.**

A arquitetura ficou significativamente melhor: o acesso só é liberado depois do provisionamento, o trabalho pesado saiu do request, há retries, lock, idempotência e uma tela central de erro.

Mas falta corrigir o retry de `FAILED` e fazer um teste operacional real com worker, falha de migration e nova tentativa. Depois desses testes, a solução pode ser promovida com risco controlado.

O requisito operacional indispensável é manter o worker da fila ativo. Sem ele, o fluxo não avança de `PROVISIONING` para `TRIAL/ACTIVE`.
