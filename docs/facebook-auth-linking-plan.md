# Plano de associação e login com Facebook no tenant

**Estado:** desenho funcional acordado; implementação por iniciar  
**Actualizado:** 2026-10-12  
**Relacionados:** [estado da migração Google](google-auth-module-migration-status.md), [análise arquitectural Google](google-auth-module-architecture-analysis.md)

## Decisão funcional

O Facebook deve ser associado nas **Definições de segurança/conta do tenant**, enquanto o utilizador já está autenticado por senha, Google ou outro método válido. Depois dessa associação, o botão Facebook do login serve para autenticar a conta tenant já ligada.

O email do Facebook não é identificador do vínculo. Pode não ser devolvido, mudar, ou não corresponder ao email registado na instituição. A chave de associação e de login é o ID devolvido pelo provider Facebook para a Facebook App configurada.

## Fluxos pretendidos

### Associar Facebook nas definições

1. Utilizador autenticado no tenant escolhe **Associar Facebook**.
2. Endpoint tenant protegido por `auth:tenant` e CSRF cria um desafio temporário de uso único ligado, no servidor, ao `tenant_id`, `tenant_user_id` e provider.
3. Browser segue para o fluxo Socialite central. O callback conclui o desafio guardado na sessão central e verifica validade, expiração e uso anterior.
4. O código procura o utilizador indicado dentro da base do tenant indicado. Se `facebook_id` estiver vazio, grava o ID Facebook e, se desejado, apenas dados de perfil aprovados, como avatar.
5. Se esse Facebook ID já estiver ligado a outro utilizador do mesmo tenant, recusa sem substituir o vínculo. Se já estiver ligado ao próprio utilizador, tratar como operação idempotente.
6. O resultado volta ao tenant com confirmação ou erro. O utilizador que iniciou a associação continua a ser a conta activa; o callback não deve autenticar outra conta por email.

### Entrar com Facebook depois da associação

1. O botão do login tenant envia a origem do tenant ao endpoint central, seguindo o mesmo padrão multi-tenant já usado pelo Google.
2. Socialite faz o redirect e valida o state através da sessão central.
3. O callback obtém o Facebook ID e procura esse ID na base do tenant seleccionado.
4. Se encontrar uma conta associada, emite o impersonation token tenant, de uso único e curta duração, e regressa ao dashboard.
5. Se não encontrar vínculo, não cria conta e não procura automaticamente por email. Mostra uma mensagem a indicar que a pessoa deve entrar pelo método normal e associar Facebook nas definições.

## Segurança do desafio de associação

Não enviar `user_id` livremente na query e não confiar apenas num ID escolhido pelo browser. O desafio deve ser imprevisível, curto e de uso único, com os campos necessários guardados no servidor:

- `tenant_id` e ID do utilizador tenant que iniciou a associação;
- provider (`facebook`);
- hash do segredo aleatório do desafio;
- data de expiração e estado de consumo;
- timestamps e, se útil para auditoria, metadados mínimos da tentativa.

A origem do pedido tem de ser uma requisição autenticada no tenant. O callback central recupera e valida o desafio associado à sessão OAuth/state. A associação deve ser idempotente para o mesmo utilizador, mas deve recusar um Facebook ID pertencente a outra conta. A unicidade de `facebook_id` na tabela `users` tenant é uma segunda barreira, não substitui as verificações de aplicação.

Definir com cuidado como o desafio fica disponível entre tenant e central: armazenamento central com token opaco de uso único é a opção preferida para o callback central; não pôr IDs de utilizador nem dados pessoais no URL. A conclusão deve consumir/invalidar o desafio mesmo nos resultados terminais e impedir replay. Como o estado do desafio e o utilizador vivem em bases diferentes, o desenho deve ser resiliente a repetição: se o ID já estiver associado ao mesmo utilizador, a repetição controlada pode concluir como sucesso sem reassociar.

## Regras que não devem ser relaxadas

- Nunca associar automaticamente por `getEmail()` do Facebook.
- Não criar `User` durante login social.
- Não substituir `facebook_id` já associado a outra identidade.
- Validar tenant e utilizador dentro do tenant indicado pelo desafio/sessão, não no tenant implícito do domínio central.
- Não autenticar no callback de associação: a conta já está autenticada no tenant e essa identidade deve permanecer a mesma.
- Login Facebook só autentica por `facebook_id` já associado naquela base tenant.
- Não guardar access/refresh tokens do Facebook se o produto não precisar de chamar a Graph API depois do login.
- Usar a mesma Facebook App/configuração em redirect e callback; o ID de utilizador é escopado à app, pelo que trocar a app pode exigir plano de migração dos vínculos.
- Manter stateful Socialite para estas rotas web; não usar `stateless()` para contornar problemas de sessão.
- Tratar `access_denied`, state inválido, email ausente e falhas técnicas com mensagens públicas allowlisted, sem expor payloads ou tokens.

## Estado actual confirmado

- O modelo tenant `App\\Models\\Tenant\\User` já permite `facebook_id`; a migration tenant declara `facebook_id` unique nullable.
- Existem `FacebookAuthService` em `Central/Auth` e `Tenant/Auth`; ambos procuram por email. O serviço central usa `Tenant\\User`, mas faz query sem envolver a operação em `tenant->run()`, e ambos chamam `Auth::guard('tenant')->login()` num fluxo cujo callback central não identificou a conexão/guarda tenant.
- `FacebookAuthController` existe, mas `php artisan route:list --path=facebook --except-vendor` não encontrou rotas activas.
- `routes/auth.php` contém rotas Facebook, mas `bootstrap/app.php` carrega `routes/web.php`; a presença em `routes/auth.php` não significa que estejam registadas.
- `config('services.facebook.redirect')` apontava para `http://localhost:8001/facebook/callback`, enquanto o exemplo de `routes/auth.php` usa `/auth/facebook/callback`. Resolver o URI antes de registar o callback no painel Meta.
- `FacebookButton` aceita `onClick`, mas a página de login tenant observada ainda não lhe passa um handler.
- Não reutilizar cegamente estes serviços/controladores antigos. Confirmar rotas, guard e conexão com testes antes de substituir/remover.

## Organização sugerida

Manter o controller HTTP pequeno e representar os dois casos de uso separadamente:

- `BeginTenantFacebookLogin` e `CompleteTenantFacebookLogin` para o login de uma conta previamente associada;
- `BeginFacebookAccountLink` e `CompleteFacebookAccountLink` para a associação iniciada nas definições por utilizador autenticado;
- `FacebookAuthError` (ou resultado equivalente allowlisted) para códigos e mensagens públicas;
- controller central para callback/redirect OAuth;
- controller tenant de associação nas definições e controller/endpoint tenant de resultado.

Não criar uma Action genérica que misture login com associação: login procura uma identidade já ligada; associação modifica a conta exacta que iniciou uma operação autenticada. Pode-se extrair código comum do provider apenas depois de ambos os fluxos estarem cobertos e houver duplicação real.

## Testes de aceitação

- Utilizador autenticado associa Facebook sem depender de email devolvido.
- Facebook ID vazio é associado ao utilizador tenant que iniciou o desafio.
- Mesmo ID já ligado ao mesmo utilizador resulta em sucesso idempotente.
- ID já ligado a outro utilizador no mesmo tenant é recusado sem alteração.
- Desafio ausente, expirado, adulterado ou já consumido não pode associar.
- Desafio de tenant A não pode alterar utilizador de tenant B.
- Cancelamento no Facebook não cria/actualiza vínculo.
- Login com Facebook ID associado autentica no guard tenant certo e redirecciona pelo token one-time.
- Login com Facebook ID sem vínculo é recusado; email igual não causa vínculo ou criação automática.
- Excepção de consulta/persistência não deixa o contexto de tenancy activo.
- Callback URI, state e redirects de erro são testados; usar `Socialite::fake()` para os casos automatizados e manter ao menos uma verificação manual local com conta de teste Meta.

## Próximo passo para o agente que implementar

1. Ler as skills Laravel, Fortify e Socialite relevantes e este plano.
2. Decidir e implementar a forma de armazenar/consumir o desafio one-time no lado central sem expor IDs no browser.
3. Construir primeiro a associação a partir das definições tenant e cobrir a matriz de segurança.
4. Só depois ligar o botão Facebook do login ao fluxo por `facebook_id`.
5. Alinhar rotas activas, `config/services.php`, `FACEBOOK_REDIRECT_URI` e URI exacto no painel Meta.
6. Só então remover os serviços/rotas Facebook legados não usados e actualizar o estado da migração.
