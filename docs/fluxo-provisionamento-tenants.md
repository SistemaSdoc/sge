# Gestão de Tenants no Central

## Objetivo

Este documento descreve como o central gere instituições/tenants, desde a criação até ao acesso, incluindo configuração assíncrona, falhas, retries e recriação da base de dados.

Serve como referência para manutenção futura. Os caminhos indicados são relativos à raiz do projeto.

## Conceitos

O sistema tem dois contextos:

- **Central:** aplicação administrativa em `sge.localhost`, onde o SuperAdmin cria, activa, acompanha, edita e remove tenants.
- **Tenant:** espaço isolado de cada instituição, normalmente em `<tenant>.sge.localhost`, com uma base MySQL própria.

Cada tenant tem:

- um registo na base central `tenants`;
- um ou mais domínios na tabela central `domains`;
- uma base própria com o nome `tenant_<id>_database`;
- dados temporários em `pending_tenant_data` enquanto a instituição e o primeiro utilizador ainda não foram criados.

## Estados do tenant

O enum está em `app/Enums/TenantStatus.php`.

| Estado | Significado | Acesso ao tenant | Acções no central |
|---|---|---:|---|
| `pending` | Criado, mas ainda não activado | Bloqueado | Activar ou activar em período de teste |
| `provisioning` | Base, migrations, seed e dados iniciais em preparação | Bloqueado | Acompanhar configuração |
| `trial` | Configuração concluída em período de teste | Permitido | Activar definitivamente ou suspender |
| `active` | Configuração concluída e tenant disponível | Permitido | Suspender ou recriar base |
| `failed` | Provisionamento terminou sem sucesso | Bloqueado | Retry ou recriar base |
| `suspended` | Tenant suspenso administrativamente | Bloqueado | Reactivar |
| `archived` | Tenant arquivado | Bloqueado | Sem transições |

`TenantStatus::canAccess()` permite apenas `trial` e `active`. `pending`, `provisioning`, `failed`, `suspended` e `archived` não podem usar o dashboard tenant.

## Fluxo 1: criar um tenant

### Entrada

O SuperAdmin usa `resources/js/pages/central/tenants/create.jsx` e envia o formulário para `TenantController@store`.

### Backend

As peças principais são:

- `app/Http/Controllers/Central/TenantController.php`
- `app/Http/Requests/Central/Tenant/StoreTenantRequest.php`
- `app/Services/Central/TenantService.php`
- `app/Models/Central/Tenant.php`
- `app/Models/Central/PendingTenantData.php`

`TenantService::createTenant()` executa numa transacção central:

1. cria o tenant com estado `pending`;
2. cria o domínio `<subdomain>.<APP_DOMAIN>`;
3. guarda nome, sigla, tipo, nome e email do administrador em `pending_tenant_data`;
4. faz commit;
5. envia a notificação de tenant pendente.

### Resultado esperado

- o tenant aparece na lista central como `Pendente de Verificação`;
- a base tenant ainda não é criada;
- o domínio tenant não dá acesso ao dashboard;
- abrir o tenant no central mostra `resources/js/pages/central/tenants/pending.jsx`.

## Fluxo 2: tenant pendente

`TenantController@show` detecta `PENDING` e renderiza `central/tenants/pending` em vez da página de detalhes vazia.

A página mostra que a instituição ainda não está configurada, apresenta `Activar tenant` e abre `AlterarStatusDialog` com as transições calculadas por `TenantService::getAvailableStatusTransitions()`.

Para `pending`, as opções são:

- `active`: Activar;
- `trial`: Activar Período de Teste (14 dias).

No domínio tenant, o login continua acessível, mas o dashboard é protegido por `CheckTenantStatus`. Um tenant pendente recebe `errors/tenant-pending-setup` com uma mensagem amigável e sem detalhes internos.

## Fluxo 3: activar e provisionar

`TenantService::transitionStatus()` abre uma transacção central, bloqueia o tenant com `lockForUpdate()`, valida a transição e chama `activateTenant()` para:

- `pending -> active`;
- `pending -> trial`;
- `failed -> active`;
- `failed -> trial`.

`activateTenant()` grava `provisioning`, o estado final pretendido, limpa o erro, actualiza timestamps e incrementa o contador. Depois dispara `TenantActivated`.

O listener está em `app/Providers/TenancyServiceProvider.php` e agenda `ProvisionTenantJob` com `afterCommit()`.

### Job de provisionamento

O job está em `app/Jobs/ProvisionTenantJob.php` e executa:

1. verifica se `tenant_<id>_database` existe;
2. cria a base se necessário;
3. executa migrations tenant;
4. executa `TenantDatabaseSeeder`;
5. executa `CreateTenantInstitution`;
6. marca o tenant como `active` ou `trial`.

O job usa `ShouldQueue`, `ShouldBeUnique`, três tentativas e backoff de 60, 300 e 900 segundos. O método `failed()` guarda o erro, marca o tenant como `failed` e escreve no log.

### Base já existente

No provisionamento normal, uma base existente é reutilizada. Isso permite continuar uma configuração interrompida sem apagar dados. Migrations e seeders devem ser repetíveis.

```text
pending
  -> provisioning
  -> criar/reutilizar base
  -> migrations
  -> seed
  -> instituição e utilizador inicial
  -> active ou trial
```

## Fluxo 4: páginas de configuração

### Central

Quando o central abre um tenant `provisioning`, `TenantController@show` renderiza `central/tenants/provisioning.jsx`.

A página não mostra métricas nem consulta a base incompleta. Faz polling a cada 3 segundos:

- enquanto `X-Tenant-Status: provisioning`, mantém a página;
- quando o estado muda, recarrega;
- depois mostra detalhes ou falha.

### Domínio tenant

`resources/js/pages/errors/tenant-configuring.jsx` mostra uma mensagem pública de configuração. Faz polling a cada 5 segundos.

As respostas de configuração incluem:

```text
HTTP 503
X-Tenant-Status: provisioning
```

O header é necessário porque uma resposta `503` não é `response.ok`.

## Fluxo 5: falha definitiva

Quando o job excede as tentativas disponíveis, `ProvisionTenantJob::failed()`:

1. grava `status = failed`;
2. guarda a mensagem em `provisioning_error`;
3. grava `provisioning_finished_at`;
4. regista a excepção;
5. deixa o job em `failed_jobs`.

### Central

`resources/js/pages/central/tenants/provisioning-failed.jsx` mostra o número de tentativas, o erro técnico e as acções de retry/retorno. Esta página é apenas para administradores do central.

### Domínio tenant

`resources/js/pages/errors/tenant-failed.jsx` devolve HTTP `503` e mostra somente uma mensagem amigável e o botão `Contactar o suporte`.

Não são enviados para esta página o erro técnico, o ID do tenant ou o número de tentativas. A página também faz polling e muda automaticamente para configuração quando um retry começa.

### Base inexistente

`app/Exceptions/TenantDatabaseNotExistException.php` trata o caso em que a tenancy não consegue inicializar porque a base ainda não existe:

- `pending`: página pública pendente;
- `provisioning`: página pública de configuração;
- `failed`: página pública amigável de falha;
- outros estados: 503 genérico.

## Fluxo 6: retry normal

O retry normal usa novamente `TenantService::transitionStatus()` e `ProvisionTenantJob`.

### Faz

- preserva a base existente;
- executa migrations pendentes;
- executa seeders repetíveis;
- cria instituição/utilizador apenas se ainda não existirem;
- recupera uma configuração parcial quando possível.

### Não faz

- não executa `migrate:fresh`;
- não apaga a base;
- não elimina dados existentes.

Este é o fluxo adequado para produção quando uma migration ou etapa de configuração falha.

## Fluxo 7: recriar a base de dados

`Recriar base de dados` é uma operação destrutiva separada do retry. Está disponível no menu do central para tenants `active`, `trial` e `failed` e exige confirmação explícita.

### Peças

- rota: `routes/web.php`, `POST tenants/{tenant}/recreate-database`;
- controller: `TenantController@recreateDatabase`;
- service: `TenantService::recreateTenantDatabase()`;
- job: `ProvisionTenantJob` com `recreateDatabase = true`;
- UI: `resources/js/pages/central/tenants/index.jsx` e `components/tenant-table.jsx`.

### Funcionamento

Antes do `DROP`, o service preserva em `pending_tenant_data`:

- nome, sigla, tipo e estado da instituição;
- nome e email do administrador.

Depois:

1. coloca o tenant em `provisioning`;
2. agenda o job em modo de recriação;
3. apaga a base existente;
4. cria uma base nova;
5. executa migrations e seeders;
6. `CreateTenantInstitution` reconstrói instituição e administrador;
7. o tenant volta a `active` ou `trial`.

A acção rejeita `pending` e `provisioning`. Se não conseguir preservar instituição e administrador, aborta antes de apagar a base.

Se uma recriação antiga já apagou a base sem backup e sem `pending_tenant_data`, os dados tenant não podem ser recuperados pelo código. É necessário backup ou recriação manual.

## Fluxo 8: autenticação e bloqueio

As rotas tenant estão em `routes/tenant.php`. O grupo base inicializa a tenancy e protege contra acesso a partir dos domínios centrais.

Ficam fora de `CheckTenantStatus`:

- login GET e POST;
- logout;
- login por token;
- consulta pública de certificados.

`CheckTenantStatus` aplica-se apenas às rotas internas autenticadas do dashboard. Assim, o utilizador pode abrir a autenticação, mas não consegue usar o dashboard enquanto o tenant estiver pendente, em configuração ou falhado.

## Seeders e idempotência

O seeder principal está em `database/seeders/Tenant/TenantDatabaseSeeder.php`.

Seeders de referência devem usar `firstOrCreate`, `updateOrCreate` ou equivalente com chaves naturais. Um seeder baseado em `create()` simples duplica dados quando é executado novamente numa base existente.

Os seeders actuais de classes, turnos, disciplinas, níveis e anos usam operações repetíveis. Isso evita novas duplicações, mas não remove cópias históricas. Para bases antigas duplicadas, usar recriação explícita ou uma migração de limpeza analisada.

## Dados de acompanhamento

A migration `database/migrations/2026_08_25_164223_add_provisioning_fields_to_tenants_table.php` adiciona:

| Campo | Uso |
|---|---|
| `provisioning_target_status` | Estado final pretendido: `active` ou `trial` |
| `provisioning_attempts` | Quantidade de activaçãos/recriações iniciadas |
| `provisioning_error` | Último erro técnico do job |
| `provisioning_started_at` | Início da operação actual |
| `provisioning_finished_at` | Fim com sucesso ou falha |

`provisioning_attempts` é histórico da vida do tenant e não é o número de tentativas do job actual.

## Fila e worker

A fila local usa a conexão `database`:

```env
QUEUE_CONNECTION=database
```

Worker manual:

```bash
php artisan queue:work database --tries=3 -vvv
```

`composer run dev` inicia servidor, worker, scheduler e Vite. Como o worker é persistente, deve ser reiniciado depois de alterar jobs:

```text
Ctrl+C
composer run dev
```

Sem worker activo, o tenant permanece em `provisioning` e os jobs ficam na tabela `jobs`. Em produção, usar Supervisor, systemd ou Horizon e monitorizar `jobs` e `failed_jobs`.

## Diagnóstico rápido

### Tenant preso em `provisioning`

1. consultar o estado e timestamps em `tenants`;
2. consultar `jobs`;
3. confirmar um `queue:work` activo;
4. verificar `storage/logs/laravel.log`;
5. verificar `failed_jobs`;
6. não clicar repetidamente em retry.

### Base já existe

No retry normal, a base é reutilizada. Se a intenção for limpar tudo, usar `Recriar base de dados`.

### Dados duplicados

Verificar se a base foi reutilizada depois de um reset apenas da base central, se o seeder antigo usava `create()` e se foi executado retry em vez de recriação.

### Worker com código antigo

Jobs são carregados em memória. Depois de alterar um job, parar e iniciar o worker; caso contrário, pode executar código de uma versão anterior.

## Peças principais

| Responsabilidade | Ficheiro |
|---|---|
| Estados | `app/Enums/TenantStatus.php` |
| Controller central | `app/Http/Controllers/Central/TenantController.php` |
| Regras e operações | `app/Services/Central/TenantService.php` |
| Job assíncrono | `app/Jobs/ProvisionTenantJob.php` |
| Instituição/admin iniciais | `app/Jobs/CreateTenantInstitution.php` |
| Evento/listener | `app/Events/TenantActivated.php`, `app/Providers/TenancyServiceProvider.php` |
| Protecção tenant | `app/Http/Middleware/CheckTenantStatus.php` |
| Base inexistente | `app/Exceptions/TenantDatabaseNotExistException.php` |
| Rotas central | `routes/web.php` |
| Rotas tenant | `routes/tenant.php` |
| Lista central | `resources/js/pages/central/tenants/index.jsx` |
| Tabela/acções | `resources/js/pages/central/tenants/components/tenant-table.jsx` |
| Diálogo de status | `resources/js/pages/central/tenants/components/alterar-status-dialog.jsx` |
| Páginas centrais | `resources/js/pages/central/tenants/pending.jsx`, `provisioning.jsx`, `provisioning-failed.jsx` |
| Páginas públicas | `resources/js/pages/errors/tenant-configuring.jsx`, `tenant-failed.jsx`, `tenant-pending-setup.jsx` |
| Dados pendentes | `app/Models/Central/PendingTenantData.php` |
| Campos de provisionamento | `database/migrations/2026_08_25_164223_add_provisioning_fields_to_tenants_table.php` |
| Unicidade dos dados pendentes | `database/migrations/2026_08_25_164407_add_unique_index_to_pending_tenant_data_tenant_id.php` |

## Checklist de produção

- [ ] migrations centrais aplicadas antes do código que usa os novos campos;
- [ ] tabelas `jobs` e `failed_jobs` disponíveis;
- [ ] worker persistente configurado e monitorizado;
- [ ] `tries`, `timeout` e `retry_after` compatíveis;
- [ ] seeders activos idempotentes;
- [ ] detalhes técnicos restritos ao central;
- [ ] backup das bases antes de recriação;
- [ ] teste `pending -> active` realizado;
- [ ] teste `pending -> trial` realizado;
- [ ] teste de falha e retry realizado;
- [ ] teste de recriação com preservação de instituição/admin realizado;
- [ ] teste com worker parado e reiniciado realizado;
- [ ] teste de duas activaçãos simultâneas realizado.

## Regra prática

```text
Retry = preservar dados e continuar
Recriar base = apagar dados e reconstruir com confirmação
Central = pode ver detalhes técnicos
Tenant = vê apenas mensagens amigáveis
Worker parado = provisioning não termina
```
