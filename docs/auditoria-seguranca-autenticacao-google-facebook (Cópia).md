# Auditoria de Segurança: Autenticação Google e Facebook

**Data:** 2026-10-12  
**Âmbito:** login social, associação/desassociação de contas, sessão tenant, tokens de impersonation, configuração OAuth e dependências PHP/JavaScript.

## Resumo executivo

O fluxo tem bons controlos: usa o fluxo de autorização do Socialite com sessão/stateful, valida o domínio tenant contra tenants registados, procura Facebook por `facebook_id`, exige `email_verified` no login Google, usa códigos de erro assinados e restringe os IDs externos a valores únicos por tenant.

A auditoria encontrou dois riscos prioritários no fluxo da aplicação:

1. **A sessão não é regenerada depois do login via token de impersonation.** O login por password regenera a sessão, mas o endpoint OAuth termina em `UserImpersonation::makeResponse()`, cujo código instalado autentica com `loginUsingId()` sem regenerar o ID da sessão.
2. **O início da associação de Google/Facebook é feito por GET.** Uma navegação cross-site pode iniciar o fluxo de associação numa sessão tenant autenticada. O Socialite valida o callback, mas isso não impede que um fluxo de associação tenha sido iniciado sem uma intenção explícita do utilizador.

Há também uma decisão de identidade pendente: o primeiro login Google ainda encontra o utilizador por email e associa automaticamente `google_id`. O Google documenta que `sub`, não email, é o identificador estável da conta; o email do utilizador tenant também não é validado como confirmado nesse momento.

**Auditoria de dependências:** `composer audit --locked` não encontrou advisories PHP. `npm audit --omit=dev --audit-level=high` reportou 10 entradas afectadas, 8 high e 2 critical, em dependências que chegam à árvore através de ferramentas de desenvolvimento/build. Não há evidência nesta auditoria de que esses pacotes estejam no bundle servido ao utilizador; a classificação em `dependencies` torna, no entanto, a árvore de produção maior do que o necessário.

## Método e limites

Foram analisados os controllers e Actions centrais, rotas tenant/central, sessão, migrations tenant, consumo de impersonation token e testes de autenticação. A pesquisa documental usou Context7 para Laravel Socialite, Laravel 13 e Stancl Tenancy; Laravel Boost para documentação correspondente às versões instaladas; documentação oficial Google e Meta; e RFC 9700, o Best Current Practice do IETF para OAuth 2.0.

Foram executados `composer audit --locked`, `npm audit --omit=dev --audit-level=high` e os testes `FacebookAuthTest`, `FacebookAssociationTest` e `GoogleAuthTest`. Os testes passaram: **36 testes**. Não foram inspeccionadas as definições efectivas no Google Cloud Console, Meta App Dashboard, variáveis de produção, regras de rede AWS ou logs do proxy. Essas verificações aparecem como itens operacionais, não como vulnerabilidades confirmadas.

## Controlos existentes

- O fluxo usa `Socialite::driver(...)->redirect()` e `user()` sem `stateless()`, preservando a validação stateful do Socialite.
- `ResolveTenantOAuthContext` aceita apenas origens HTTP(S) simples e exige domínio registado e tenant acessível antes de iniciar OAuth.
- O login Facebook procura directamente o `facebook_id`; não depende de email, que pode estar ausente.
- O login Google exige o claim de email verificado do provider.
- `google_id` e `facebook_id` têm índices únicos na tabela tenant.
- A associação é separada do login e recebe um payload cifrado com tenant, utilizador e expiração.
- Erros de login cross-domain usam códigos allowlisted em rotas temporárias assinadas. Os detalhes técnicos ficam no log, não na URL de retorno.
- O token de impersonation é guardado centralmente, ligado ao tenant/utilizador e tem TTL de 60 segundos; o pacote elimina-o após o consumo.
- O logout tenant invalida a sessão e regenera o token CSRF. O login por password também regenera a sessão.
- O controller de segurança impede desconectar a última opção de acesso: exige password, outro provider ou passkey.

Referências principais: [GoogleAuthController](../app/Http/Controllers/Central/Auth/GoogleAuthController.php), [FacebookAuthController](../app/Http/Controllers/Central/Auth/FacebookAuthController.php), [ResolveTenantOAuthContext](../app/Actions/Central/Auth/ResolveTenantOAuthContext.php), [CompleteTenantFacebookLogin](../app/Actions/Central/Auth/CompleteTenantFacebookLogin.php), [CompleteTenantGoogleLogin](../app/Actions/Central/Auth/CompleteTenantGoogleLogin.php), [AuthenticatedSessionController](../app/Http/Controllers/Tenant/Auth/AuthenticatedSessionController.php) e [migration de utilizadores tenant](../database/migrations/tenant/0006_01_01_000000_create_users_table.php).

## Achados prioritários

### A1. Sessão não regenerada no login por impersonation

**Severidade:** Alta  
**Confiança:** Confirmado no código instalado.

O login tradicional chama `$request->session()->regenerate()`. Já `AuthenticatedSessionController::token()` devolve directamente `UserImpersonation::makeResponse($token)`. Na versão instalada de Stancl, `makeResponse()` valida tenant e TTL, chama `Auth::guard(...)->loginUsingId()`, apaga o token e redirecciona, mas não regenera a sessão. Isso deixa o caminho Google/Facebook sem a rotação de sessão aplicada no login por password e pode permitir session fixation se um atacante conseguir fixar/conhecer o ID pré-autenticação.

**Acção recomendada:** regenerar o ID de sessão no próprio endpoint tenant depois de `makeResponse()` autenticar o utilizador e antes de devolver a resposta. Adicionar teste que prove que o ID mudou, o guard tenant ficou autenticado e o token não pode ser consumido uma segunda vez. Não editar o vendor.

Referências: [AuthenticatedSessionController](../app/Http/Controllers/Tenant/Auth/AuthenticatedSessionController.php), [implementação instalada de UserImpersonation](../vendor/stancl/tenancy/src/Features/UserImpersonation.php).

### A2. Associação de provider começa por GET

**Severidade:** Alta, com pré-condições de browser/provider  
**Confiança:** Confirmado que a rota é GET; exploração depende do contexto da vítima.

Os botões do perfil navegam para endpoints GET de associação. Esses endpoints criam o desafio de associação e iniciam OAuth. `SameSite=Lax` ajuda contra vários CSRF, mas permite cookies em navegações de topo por GET; não é substituto de uma operação iniciada por POST com CSRF. Um utilizador que seja levado a iniciar o fluxo e tenha uma sessão activa no provider errado pode acabar por associar essa identidade ao perfil tenant. O callback stateful protege a resposta OAuth, mas não prova que a acção de associação começou com intenção explícita do utilizador.

**Acção recomendada:** mudar os endpoints de início de associação para POST com CSRF, preservar confirmação no provider e considerar password confirmation para a alteração de identidade. Manter o callback OAuth como GET, pois é o redirect do provider. Adicionar teste de rejeição sem CSRF para o início da associação.

Referências: [rotas de segurança](../routes/settings.php), [componente de segurança do perfil](../resources/js/pages/tenant/users/profile/components/security-data.jsx), [SecurityController](../app/Http/Controllers/Tenant/Settings/SecurityController.php).

### A3. Primeiro login Google associa por email mutável

**Severidade:** Média/Alta, conforme política de identidade  
**Confiança:** Confirmado que o código associa por email; impacto depende da qualidade e ciclo de vida dos emails tenant.

`CompleteTenantGoogleLogin` exige `email_verified=true` no Google, mas procura primeiro `Tenant\User` por email e grava `google_id` automaticamente quando o campo ainda está vazio. Não exige `email_verified_at` no registo tenant. Assim, email é usado como ponte de identidade inicial, apesar de poder ser alterado ou reciclado e de o registo local poder nunca ter sido validado. O Google recomenda usar `sub` como identificador persistente; email não é um identificador único estável. A própria análise arquitectural Google existente assinala que a política de ownership/linking ainda está pendente.

**Acção recomendada:** decidir formalmente a política antes de manter o auto-link. A opção mais segura é permitir login apenas por `google_id` já associado e exigir associação iniciada por utilizador autenticado para novos vínculos. Se o auto-link por email for requisito, exigir que o email tenant esteja verificado e definir uma política explícita para emails institucionais/reciclados. Não confiar em `hd` enviado na solicitação; validar a claim recebida se houver restrição de domínio.

Referências: [CompleteTenantGoogleLogin](../app/Actions/Central/Auth/CompleteTenantGoogleLogin.php), [análise arquitectural Google](google-auth-module-architecture-analysis.md), [OIDC do Google](https://developers.google.com/identity/openid-connect/openid-connect).

### A4. Desafio de linking não está ligado ao tenant/provider da sessão nem é one-time

**Severidade:** Alta, condicionada à exposição/reutilização do desafio  
**Confiança:** A falta de comparação entre tenants/providers está confirmada; a exploração exige obter/reutilizar um desafio válido.

Os payloads Google e Facebook contêm os mesmos campos (`tenant_id`, `user_id`, `exp`) cifrados com a mesma `APP_KEY`. O redirect central resolve e guarda um tenant na sessão, mas as Actions de conclusão escolhem o tenant a partir do payload cifrado e não comparam os dois IDs. Também não há `provider`/`purpose` nem nonce consumido uma única vez. Se um desafio válido for obtido de uma URL/log e reutilizado com outro tenant registado na query, a identidade social pode ser associada ao utilizador indicado no payload enquanto o retorno é dirigido ao tenant da sessão. A cifragem protege o conteúdo contra alteração; não vincula o conteúdo à sessão, ao tenant validado ou a um único uso.

**Acção recomendada:** passar o tenant validado da sessão central à Complete Action e recusar se diferir do tenant cifrado; incluir `provider`/`purpose`; criar um nonce aleatório opaco, ligado à sessão e consumido atomicamente uma única vez. Evitar expor payloads reutilizáveis na query string. Não reutilizar o `state` do Socialite para transportar contexto de negócio.

Referências: [BeginTenantGoogleLogin](../app/Actions/Central/Auth/BeginTenantGoogleLogin.php), [BeginTenantFacebookLogin](../app/Actions/Central/Auth/BeginTenantFacebookLogin.php), [CompleteTenantGoogleAccountLink](../app/Actions/Central/Auth/CompleteTenantGoogleAccountLink.php), [CompleteTenantFacebookAccountLink](../app/Actions/Central/Auth/CompleteTenantFacebookAccountLink.php).

### A5. Token de impersonation é bearer em URL e o consumo não é atómico

**Severidade:** Média  
**Confiança:** Confirmado no pacote instalado; exposição real depende de logging/infra.

O token de 128 caracteres tem protecções úteis: é central, tenant-bound, expira em 60 segundos e é apagado ao consumir. Mesmo assim, viaja no path `/token/{token}` e pode ser recolhido por access logs, APM, histórico do browser ou monitorização. Além disso, o pacote faz `find`, valida TTL, autentica e só depois apaga; não há lock/transacção que torne o consumo estritamente atómico para duas requisições simultâneas.

**Acção recomendada:** redigir o segmento `/token/*` em logs de proxy/APM; não carregar conteúdo externo nessa resposta e aplicar `Referrer-Policy: no-referrer`. Considerar consumo atómico do token (transacção/lock ou operação de delete condicionada) e manter o TTL curto. A regeneração da sessão é tratada em A1.

Referência: [UserImpersonation instalado](../vendor/stancl/tenancy/src/Features/UserImpersonation.php).

### A6. Cookies seguros e cabeçalhos dependem do ambiente

**Severidade:** Média, dependente de produção  
**Confiança:** Configuração local observada; produção não foi inspeccionada.

`SESSION_SECURE_COOKIE` controla `session.secure`, mas não tem valor definido no `.env.example`; a configuração local resolve para `null`. `SESSION_DOMAIN=null` mantém o cookie host-only, e `HttpOnly=true`/`SameSite=Lax` são bons valores para este fluxo. Não encontrei `Referrer-Policy`, HSTS, CSP ou `X-Frame-Options` definidos na aplicação; podem ser configurados no ALB/CDN.

O bootstrap confia em todos os proxies (`at: '*'`). O comentário do código documenta a condição necessária: a instância deve aceitar tráfego exclusivamente do Security Group do ALB. Se essa barreira não existir, headers `X-Forwarded-*` podem ser forjados e afectar o esquema/URL OAuth.

**Acções recomendadas:** em produção definir `SESSION_SECURE_COOKIE=true`, validar `APP_URL` e callbacks HTTPS exactos; confirmar o isolamento da instância ao ALB; aplicar HSTS e `Referrer-Policy` na camada de entrada; rever CSP/frame-ancestors sem bloquear os redirects dos providers. Confirmar a configuração efectiva por ambiente sem expor `.env`.

Referências: [configuração de sessão](../config/session.php), [bootstrap/proxies](../bootstrap/app.php), [.env.example](../.env.example).

### A7. Logs podem guardar mensagens de excepção do provider

**Severidade:** Baixa/Média  
**Confiança:** Confirmado que a mensagem é registada; conteúdo sensível é potencial, não observado nesta auditoria.

Os controllers centrais registam `getMessage()` de excepções inesperadas. Mensagens de bibliotecas HTTP/provider podem incluir parâmetros ou respostas que não devem ficar em logs de autenticação.

**Acção recomendada:** preferir classe, provider, tenant ID interno e correlation ID; redigir `code`, `state`, access/refresh token, app secret e corpos/respostas do provider. Restringir retenção e acesso aos logs.

Referências: [GoogleAuthController](../app/Http/Controllers/Central/Auth/GoogleAuthController.php), [FacebookAuthController](../app/Http/Controllers/Central/Auth/FacebookAuthController.php).

### A8. Google avatar pode falhar o login por limite de coluna

**Severidade:** Disponibilidade, não takeover  
**Confiança:** Confirmado que o login grava URL externa em `avatar` `VARCHAR(255)`; ocorrência depende do tamanho retornado.

O fluxo de linking Facebook foi corrigido para não gravar o avatar do provider. O login Google ainda actualiza `avatar` com `getAvatar()` junto com `google_id`. URLs longos podem exceder o limite da coluna, abortar o `UPDATE` e fazer falhar o login.

**Acção recomendada:** não actualizar avatar como parte da autenticação, ou armazenar uma URL validada/normalizada numa coluna compatível fora do caminho crítico do login. O avatar não deve determinar se a autenticação é concluída.

Referências: [CompleteTenantGoogleLogin](../app/Actions/Central/Auth/CompleteTenantGoogleLogin.php), [migration users tenant](../database/migrations/tenant/0006_01_01_000000_create_users_table.php).

## Advisories de dependências

### PHP

`composer audit --locked` em 2026-10-12: **nenhum advisory conhecido** para o lockfile PHP. Versões verificadas: Laravel 13.35.0, Socialite 5.31.0 e Stancl Tenancy 3.10.1. Isto não substitui actualizações regulares nem cobre vulnerabilidades no código da aplicação.

### JavaScript

`npm audit --omit=dev --audit-level=high` reportou **10 entradas afectadas (8 high, 2 critical)**. O grafo inclui advisories transitivos de `shadcn`, `concurrently`, Vite/Tailwind e ferramentas relacionadas. A auditoria não prova que estes pacotes estejam no bundle do browser ou sejam executados no servidor; `npm ls` mostra `shadcn` e `concurrently` como dependências directas apesar de serem ferramentas de CLI/desenvolvimento. Rever a classificação em `package.json` e o lockfile; corrigir advisories sem aplicar automaticamente uma migração major do CLI.

| Dependência instalada / caminho | Advisory e impacto reportado | Exposição observada |
|---|---|---|
| `@modelcontextprotocol/sdk@1.30.0` via `shadcn` | [GHSA-6qxp-vccf-f47h](https://github.com/advisories/GHSA-6qxp-vccf-f47h): cliente OAuth pode enviar credenciais para servidor de autorização escolhido pelo servidor MCP. | CLI `shadcn`, não identificado como código do fluxo web. |
| `shell-quote@1.9.0` via `concurrently@9.2.4` | [GHSA-pqg4-j6r4-53mv](https://github.com/advisories/GHSA-pqg4-j6r4-53mv): command injection na função `quote()`. | `concurrently` é usado pelos scripts de desenvolvimento; avaliar apenas se entrada não confiável chega aos comandos. |
| `braces@3.0.3` via `shadcn` → `fast-glob` → `micromatch` | [GHSA-vfj7-8cjw-p6xm](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm): stack-exhaustion DoS com padrões profundamente aninhados. | Ferramentas de CLI/build. O audit indicou que `npm audit fix --force` escolheria `shadcn@1.0.0`, mudança major; não foi aplicado. |
| `source-map-js@1.2.1` via Vite/Tailwind/PostCSS | [GHSA-68fv-2mgg-jv7q](https://github.com/advisories/GHSA-68fv-2mgg-jv7q): DoS no event loop ao processar source maps indexados. | Toolchain de build; confirmar que source maps não são processados por um serviço runtime exposto. |

O total também inclui advisories transitivos em `@ts-morph/common`, `fast-glob`, `micromatch` e `ts-morph`. Rever `npm audit` após actualizar as dependências e depois de cada alteração ao lockfile. Não executar `npm audit fix --force` sem rever a alteração major proposta.

## Plano de hardening recomendado

1. **Antes de produção:** regenerar o ID de sessão no consumo do token de impersonation e testar rotação, tenant correto, token expirado e uso repetido.
2. **Antes de produção:** mudar início de associação para POST com CSRF e adicionar confirmação explícita; testar navegação cross-site e associação cancelada.
3. **Decisão de identidade:** aprovar uma política de ownership Google. Preferência: `google_id`/`sub` como identidade de login; linking por email apenas com email local verificado e fluxo explícito.
4. **Estados de ligação:** incluir provider/purpose, nonce aleatório one-time e armazenamento central com consumo atómico; não transportar payload cifrado reutilizável na query.
5. **OAuth protocol:** manter stateful Socialite; activar PKCE (`enablePKCE()`) para cada driver se o provider/versão suportar e passar testes reais. RFC 9700 recomenda PKCE também para clientes confidenciais; não substituir state por um parâmetro artesanal.
6. **Produção:** garantir HTTPS e URI exacto registado no Google Cloud e Meta; activar Meta Strict Mode e HTTPS; nunca expor `FACEBOOK_CLIENT_SECRET`/`GOOGLE_CLIENT_SECRET` no cliente; confirmar `SESSION_SECURE_COOKIE=true`, ALB-only ingress e headers de segurança.
7. **Operação:** redigir tokens nos logs, reduzir dados nos logs OAuth e rever dependências npm toolchain.
8. **Validação contínua:** conservar testes com Socialite fake para state, tenant errado, provider ID inexistente/conflitante, cancelamento, estado expirado, sessão regenerada e token de impersonation consumido uma única vez; realizar pelo menos um smoke test por provider num ambiente de staging.

## Fontes

- [RFC 9700 — OAuth 2.0 Security Best Current Practice](https://www.rfc-editor.org/rfc/rfc9700.html): state/CSRF, PKCE, redirect URI exacto, leakage por URL/referrer e reverse proxies.
- [Laravel Socialite](https://laravel.com/docs/13.x/socialite) e [código Socialite 5.x](https://github.com/laravel/socialite): redirect/callback stateful e suporte PKCE (`enablePKCE`).
- [Laravel 13 Authentication](https://laravel.com/docs/13.x/authentication) e [Signed URLs](https://laravel.com/docs/13.x/urls#signed-urls).
- [Google OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect): state, redirect URI exacto, `sub` estável, email mutável e `email_verified`.
- [Meta Login Security](https://developers.facebook.com/docs/facebook-login/security/): state, Strict Mode, HTTPS, redirect allowlist e proteção do App Secret.
- [Stancl Tenancy v3 — User Impersonation](https://tenancyforlaravel.com/docs/v3/features/user-impersonation/): token tenant-bound, TTL e consumo.
- [Composer audit](https://getcomposer.org/doc/03-cli.md#audit) e advisories GitHub listados na secção npm.
