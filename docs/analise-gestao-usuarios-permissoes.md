# Análise rápida: gestão de usuários e permissões individuais

Data: 2026-09-25  
Escopo: gestão de usuários tenant, permissões diretas, roles e autorização relacionada.

## Resumo executivo

O módulo tem um fluxo funcional básico, mas apresenta riscos altos de integridade e autorização. O defeito mais grave está em `UserController`: os métodos `edit` e `update` recebem um usuário-alvo por route model binding e depois substituem essa variável pelo usuário autenticado. Assim, a tela pode carregar e a operação pode atualizar o usuário errado, enquanto a autorização foi feita originalmente contra outro alvo.

Também existem divergências entre permissões semeadas e permissões consultadas, autorização duplicada/incompleta na request de permissões e ausência de testes para isolamento entre alvo e ator. Recomenda-se corrigir primeiro o alvo dos CRUDs e a autorização server-side; só depois ajustar a UX e ampliar a cobertura.

## Achados

### Crítico: o alvo é sobrescrito em `edit` e `update`

Arquivo: [app/Http/Controllers/Tenant/UserController.php](../app/Http/Controllers/Tenant/UserController.php#L85-L126)

- `edit(User $user)` autoriza o alvo recebido, mas em seguida executa `$user = Auth::guard('tenant')->user()`.
- `update(UpdateUserRequest $request, User $user)` repete o mesmo padrão.
- A chamada ao action usa a variável sobrescrita, portanto uma edição destinada a outro usuário pode alterar o perfil do ator autenticado.
- O `if ($user?->isSubdirector() && $user->is($user))` torna-se sempre verdadeiro para um subdirector, pois compara o objeto consigo mesmo. Isso bloqueia a alteração do próprio perfil, mas não corrige o alvo perdido.

Impacto: corrupção de dados, alteração silenciosa do usuário errado e comportamento inconsistente entre a autorização exibida e a operação realizada.

Correção recomendada: manter `$target`/`$user` recebido pela rota e usar outra variável, como `$currentUser`, exclusivamente para o ator autenticado. A regra de autoedição deve comparar `$currentUser->is($target)`.

### Alto: a request de permissões não autoriza a operação

Arquivo: [app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php](../app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php#L10-L22)

`authorize()` retorna sempre `true`. A proteção depende exclusivamente do controller, apesar de a request conhecer o usuário autenticado e o alvo da rota.

Impacto: a regra de autorização fica fácil de ignorar em outro endpoint que reutilize a request e não há uma barreira própria no boundary de entrada. Também dificulta testar e manter a regra de negócio.

Correção recomendada: autorizar o alvo com `Gate::forUser($actor)->authorize('update', $target)` ou centralizar uma ability específica para gestão de permissões. Manter a checagem no controller/action como defesa adicional quando houver regra de negócio sensível.

### Alto: permissão para gerir permissões individuais está ligada à ability errada

Arquivo: [app/Services/Tenant/Users/UserManagementService.php](../app/Services/Tenant/Users/UserManagementService.php#L30-L37)

O campo `can.manage_permissions` é calculado com `$actor->can('update', $user)`, a mesma ability usada para editar dados cadastrais. A ação de permissões, porém, possui uma regra própria (`acessos.create`) e a policy de usuário também tenta usar uma permissão de gestão.

Impacto: qualquer ator autorizado a editar dados pode receber na UI o botão de permissões, mesmo que não esteja autorizado a atribuí-las; ou o botão pode ficar oculto enquanto a rota ainda aceita a operação por outra combinação de permissões.

Correção recomendada: separar explicitamente `manage_permissions` de `update`, usando uma ability/policy consistente e a mesma regra no backend e no frontend.

### Alto: nomes de permissões de gestão são inconsistentes

Arquivos:

- [app/Policies/Tenant/UserPolicy.php](../app/Policies/Tenant/UserPolicy.php#L60-L68)
- [database/seeders/Tenant/RolePermissionSeeder.php](../database/seeders/Tenant/RolePermissionSeeder.php#L160-L168)
- [tests/Unit/Tenant/UserManagementPolicyTest.php](../tests/Unit/Tenant/UserManagementPolicyTest.php#L1-L30)

A policy exige `usuarios.gerir`, mas o seeder do papel concede `utilizadores.gerir`. O teste unitário também espera `utilizadores.gerir`, enquanto o código de produção consulta `usuarios.gerir`.

Impacto: a capacidade de gerir usuários pode ser negada mesmo para papéis corretamente semeados. O resultado depende de permissões criadas manualmente ou de dados antigos.

Correção recomendada: escolher um único identificador, migrar/normalizar dados existentes e adicionar um teste de integração que valide o papel semeado contra a ability efetiva.

### Alto: a autorização do alvo e a autorização da instituição não são uniformes

Arquivo: [app/Policies/Tenant/UserPolicy.php](../app/Policies/Tenant/UserPolicy.php#L10-L68)

`create()` verifica apenas `usuarios.create`, sem restringir explicitamente a instituição do ator. O isolamento por instituição é aplicado no `index()` e em `update/delete`, mas não está claramente concentrado numa regra única de domínio.

Impacto: dependendo da intenção do sistema para SuperAdmin, Director e Subdirector, pode ser possível criar usuário com `instituicao_id` derivado do ator sem uma regra formal, ou criar usuários em contextos em que a policy deveria impedir a operação. A regra está espalhada entre controller, service, request e action.

Correção recomendada: documentar e testar a matriz ator/alvo/instituição, incluindo SuperAdmin, Director, Subdirector e usuário comum. A instituição do alvo deve ser sempre definida server-side, nunca pelo payload.

### Médio: permissões diretas podem ultrapassar a capacidade real do ator em alguns cenários

Arquivo: [app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php](../app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php#L25-L39)

Para qualquer ator que não seja Subdirector, a lista permitida vem de `RoleManagementService::permissions()` sem filtrar pela capacidade concreta do ator. A validação garante que a permissão existe no catálogo tenant, mas não que o ator pode delegá-la.

Impacto: um papel com acesso a usuários, mas sem autoridade equivalente sobre todos os módulos, pode atribuir permissões elevadas a outro usuário. A restrição atual para Subdirector é baseada apenas na diferença entre roles Director/Subdirector, não numa política geral de delegação.

Correção recomendada: definir uma lista de permissões delegáveis por actor/role ou uma policy de delegação. Validar o payload no servidor contra essa lista e impedir também a atribuição de permissões que permitam elevar o próprio ator ou criar um administrador equivalente.

### Médio: o link de permissões no frontend usa URL manual

Arquivo: [resources/js/pages/tenant/users/components/user-table.jsx](../resources/js/pages/tenant/users/components/user-table.jsx#L120-L132)

O link usa ``/dashboard/users/${user.id}/permissions`` diretamente, embora o projeto tenha Wayfinder e a página de permissões já use action gerada no submit.

Impacto: mudanças de prefixo, domínio tenant, parâmetros ou nomes de rota podem quebrar somente a navegação da tabela. Também cria uma exceção ao padrão de integração do projeto.

Correção recomendada: importar e usar a função Wayfinder da rota `UserPermissionController@create`.

### Médio: possível N+1 ao serializar usuários

Arquivo: [app/Services/Tenant/Users/UserManagementService.php](../app/Services/Tenant/Users/UserManagementService.php#L10-L35)

O índice carrega `roles`, mas para cada usuário chama `getDirectPermissions()` e `getPermissionsViaRoles()`. Dependendo do estado do carregamento do Spatie, isso pode gerar consultas adicionais por usuário.

Impacto: degradação da página de usuários à medida que a instituição cresce.

Correção recomendada: medir com `DB::listen`/profiler e carregar as relações de permissões explicitamente (`roles.permissions`, `permissions`) ou criar uma transformação que use as relações já carregadas.

### Médio: a atualização de permissões não usa transação nem auditoria

Arquivo: [app/Actions/Tenant/User/UpdateUserPermissions.php](../app/Actions/Tenant/User/UpdateUserPermissions.php#L7-L18)

O action executa apenas `syncPermissions()` e retorna o usuário. Não há transação explícita, registro de quem alterou, motivo, permissões removidas/adicionadas ou proteção contra alterações concorrentes.

Impacto: alterações administrativas ficam sem rastreabilidade e uma falha parcial pode ser difícil de diagnosticar. Para um módulo de acesso, a ausência de auditoria é um risco operacional relevante.

Correção recomendada: envolver a operação no padrão transacional do projeto e registrar auditoria com ator, alvo, diff e tenant/instituição.

## Lacunas de testes

O teste [tests/Feature/TenantUserPermissionsTest.php](../tests/Feature/TenantUserPermissionsTest.php#L1-L51) cobre apenas o caso feliz de um administrador atualizar permissões diretas. Faltam, no mínimo:

- atualizar um alvo diferente do ator e confirmar que somente o alvo muda;
- impedir que Subdirector altere a si próprio;
- impedir acesso a alvo de outra instituição;
- impedir acesso a Director protegido por ator não autorizado;
- rejeitar permissões fora da lista delegável do ator;
- verificar que permissões herdadas não são removidas por `syncPermissions()`;
- validar o comportamento com roles semeados, especialmente `usuarios.gerir`/`utilizadores.gerir`;
- impedir atribuição de permissões administrativas que gerem elevação de privilégio;
- verificar que o botão da UI corresponde à autorização efetiva do backend.

## Plano de correção sugerido

1. Corrigir imediatamente o uso do usuário-alvo em `UserController::edit/update` e adicionar teste de regressão.
2. Unificar os nomes das permissões de gestão e atualizar seeder, policy e testes.
3. Criar uma ability/policy explícita para gestão de permissões individuais.
4. Fazer a `UpdateUserPermissionsRequest` negar por padrão e validar a capacidade de delegação no servidor.
5. Alinhar `can.manage_permissions`, rotas e frontend com a mesma regra server-side.
6. Medir consultas da listagem e corrigir eventual N+1.
7. Adicionar auditoria para mudanças de permissões.

## Nota de verificação

Esta seção registra o estado inicial da análise. As correções e validações posteriores estão documentadas abaixo.

## Estado após correção

Implementado em 2026-09-25:

- corrigido o alvo sobrescrito em `UserController::edit/update`;
- adicionada a ability `managePermissions`, com isolamento por instituição e proteção de Subdirector/Director;
- requests de usuários, roles e permissões passaram a autorizar pelo guard `tenant`;
- unificado `usuarios.gerir` no catálogo e nos roles Director/Subdirector;
- restringida a atribuição de roles/permissões ao conjunto delegável pelo ator;
- corrigido o filtro de permissões agrupadas para Subdirector;
- corrigido o endpoint legado de gestão de acessos;
- substituído o link manual de permissões por Wayfinder;
- reduzido o risco de N+1 na listagem de usuários e acessos;
- sincronização de permissões passou a ser transacional e gerar log estruturado com diff;
- criada a factory tenant e ajustados os testes para o schema tenant.

Validação executada: 14 testes específicos passaram e Pint passou. O `npm run types:check` continua bloqueado por configuração global existente em `tsconfig.json`, que define `ignoreDeprecations: "6.0"`, valor rejeitado pela versão instalada do TypeScript. A auditoria implementada usa logs estruturados; não foi criado um histórico persistente em tabela porque o projeto não possui infraestrutura de auditoria identificada.