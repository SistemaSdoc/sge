# Estado da migração do módulo Google Auth

**Data:** 2026-10-10  
**Estado:** migração estrutural concluída; decisão de ownership pendente  
**Plano de referência:** [google-auth-module-architecture-analysis.md](google-auth-module-architecture-analysis.md)

## Resumo

O módulo foi reorganizado sem alterar rotas públicas, host local de OAuth, sessão central, regras de acesso, mensagens do `Alert` ou o fluxo de impersonation. A implementação continua a procurar o utilizador na base de dados do tenant. Essa decisão foi preservada para evitar mudar comportamento durante uma refactor, mas ainda precisa de ser reconciliada com [google-auth-access-control-plan.md](google-auth-access-control-plan.md), que descreve lookup central.

## Estado por fase

| Fase                          | Estado    | Resultado                                                                                                                                                                 |
| ----------------------------- | --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Inventário de referências     | Concluída | Confirmados controllers, serviços, rotas, login React, migrations e testes; não existe `.ai/rules` neste repositório.                                                     |
| Delimitação dos casos de uso  | Concluída | Início e conclusão do login separados em operações distintas.                                                                                                             |
| Extracção das Actions         | Concluída | Criadas `BeginTenantGoogleLogin` e `CompleteTenantGoogleLogin`.                                                                                                           |
| Separação do erro tenant      | Concluída | Criado `GoogleAuthErrorController`; o `AuthenticatedSessionController` deixou de interpretar o endpoint Google.                                                           |
| Centralização dos códigos     | Concluída | `GoogleAuthError` enum contém os códigos e mensagens permitidos.                                                                                                          |
| Serviços Google sem consumers | Concluída | Removidos `GoogleAuthService` central e tenant depois de confirmar que não tinham callers activos.                                                                        |
| Compatibilidade do fluxo      | Validada  | Rotas existentes e nomes preservados; a rota signed tenant continua com middleware `signed:relative`.                                                                     |
| Ownership do utilizador       | Pendente  | Decidir se a fonte de verdade é `Tenant\\User` ou `Central\\User` e actualizar plano, implementação e testes em conjunto.                                                 |
| Limpeza de rotas antigas      | Adiada    | `routes/auth.php` permanece fora do routing bootstrap e contém referências/comentários antigos de autenticação; rever separadamente, sem alterar Facebook nesta migração. |

## Ficheiros da arquitectura actual

- `app/Actions/Central/Auth/BeginTenantGoogleLogin.php`: valida e resolve a origem tenant, guarda o tenant validado na sessão central e inicia Socialite.
- `app/Actions/Central/Auth/CompleteTenantGoogleLogin.php`: valida email/tenant, procura e associa o utilizador tenant, gere o contexto tenant em excepções e cria o impersonation token.
- `app/Http/Controllers/Central/Auth/GoogleAuthController.php`: coordena as duas operações, trata respostas OAuth/erros e limpa o contexto da sessão.
- `app/Enums/Auth/GoogleAuthError.php`: define o conjunto finito de códigos e mensagens públicas.
- `app/Http/Controllers/Tenant/Auth/GoogleAuthErrorController.php`: converte um código assinado em flash local para o login tenant.
- `app/Http/Controllers/Tenant/Auth/AuthenticatedSessionController.php`: continua responsável pelo login normal, logout e consumo do token de impersonation.

## Comportamento preservado

1. O botão local inicia em `http://localhost:8001/auth/google/redirect`; produção continua a usar a rota Wayfinder existente.
2. O callback OAuth continua em `http://localhost:8001/auth/google/callback` no ambiente local e o state continua gerido pelo Socialite.
3. A origem recebida é validada contra um domínio tenant registado e acessível antes de ser guardada na sessão central.
4. O email Google tem de estar verificado e corresponder a um utilizador já existente na base do tenant. Não há criação de conta durante OAuth.
5. Um conflito de `google_id` é recusado; o utilizador não autenticado recebe um link assinado temporário para o domínio tenant.
6. O endpoint de erro tenant aceita apenas códigos enumerados através de `signed:relative`; o flash `googleError` só é criado depois de o browser voltar ao tenant.
7. O token de impersonation continua a ser emitido e consumido pelo Stancl com o tenant e guard actuais.

## Validação executada

- `php artisan test --compact tests/Feature/Auth/GoogleAuthTest.php`: **38 testes passaram** após a migração estrutural.
- `vendor/bin/pint --dirty --format agent`: executado nos ficheiros PHP alterados.
- `php artisan wayfinder:generate --no-interaction`: rota/controller mantém os contratos frontend; a importação Wayfinder existente não precisou de alteração.
- `npm run build`: compilação frontend concluída após a geração Wayfinder.
- `php artisan route:list --path=auth/google` e `php artisan route:list --path=token`: rotas centrais, tenant signed-error e token verificadas.
- `git diff --check`: sem problemas de whitespace na última validação.

## Decisões e trabalho seguinte

1. Decidir formalmente onde vive a conta que autoriza Google. O código e testes actuais usam a base tenant; o plano funcional antigo diz base central. Não alterar ownership por inferência.
2. Se a decisão for central, desenhar como o utilizador central é ligado ao utilizador tenant, qual guard será autenticado e como impedir acesso entre tenants antes de alterar a Action.
3. Rever se a associação automática por email verificado deve aceitar todos os domínios ou exigir domínio institucional confiável.
4. Rever `routes/auth.php` e serviços Facebook numa tarefa própria. Esta migração apenas removeu os serviços Google sem consumidores; não assumiu que os fluxos Facebook duplicados são seguros ou equivalentes.
5. Depois de resolver ownership, acrescentar/ajustar os testes de integração da nova regra antes de alterar dados ou migrations.

## Nota

Este documento regista a migração estrutural e não substitui a decisão de regra de negócio sobre a fonte de verdade do utilizador. A suite usa `Socialite::fake()`; a validação manual real com Google continua a ser um passo separado de release.
