# Relatório: fluxo actual da gestão de usuários

Data: 2026-09-29  
Escopo: CRUD de usuários no tenant, atribuição de roles, permissões directas, perfis associados e caminhos paralelos de gestão de acessos.

## 1. Resumo executivo

O módulo tem três mecanismos distintos que se cruzam na interface:

1. **Role:** conjunto de permissões herdadas, atribuído ao usuário pelo CRUD.
2. **Permissão directa:** permissão atribuída directamente ao usuário pela tela “Gerir permissões”.
3. **Perfil de domínio:** registro associado ao usuário, actualmente criado automaticamente para `Professor`; o perfil de `Aluno` tem outro fluxo.

O CRUD básico está implementado e a criação de Professor tem teste de integração do action. A lógica, porém, ainda não constitui um contrato de negócio uniforme: criação sem role é permitida, edição sem `roles` pode remover todas as roles, a criação genérica pode aceitar `Aluno` embora não crie o respectivo perfil académico, e retirar `Professor` não elimina o perfil de Professor. Existe também um caminho legado de gestão de acessos que duplica parte da função da tela individual.

Este documento descreve o comportamento observado no código actual. Os itens “decisão em aberto” são matéria para discussão; não são alterações já feitas.

## 2. Rotas e entrada no módulo

As rotas ficam no grupo tenant autenticado e verificado, com middleware de role `SuperAdmin|Director|Subdirector|Secretaria|Professor|Aluno`.

- `GET /dashboard/users`: lista usuários; exige `usuarios.viewAny`.
- `GET /dashboard/users/create`: formulário; exige `usuarios.create`.
- `POST /dashboard/users`: cria; request e controller verificam `create`.
- `GET /dashboard/users/{user}/edit`: formulário; exige `update` sobre o alvo.
- `PUT/PATCH /dashboard/users/{user}`: actualiza; request e controller verificam `update`.
- `DELETE /dashboard/users/{user}`: remove; exige `delete`.
- `GET /dashboard/users/{user}/permissions`: formulário de permissões directas; exige `managePermissions`.
- `PUT /dashboard/users/{user}/permissions`: sincroniza permissões directas; request e controller autorizam `managePermissions`.
- `GET /dashboard/roles` e recursos seguintes: gestão de roles separada, governada pela `RolePolicy` e `acessos.*`.

Há ainda `/dashboard/access-management`, um caminho legado que combina edição de roles e permissões directas. Ele sobrepõe responsabilidades de `RoleController`, `UserController` e `UserPermissionController` e pode causar diferenças de regra entre telas.

Nota: `UserController::show()` não é actualmente o destino de `Route::resource('users', ...)->except(['show'])`. A rota `GET /users/{user}` é registada explicitamente para `UserProfileController::show()`.

## 3. Quem pode fazer o quê

### Listar

`UserPolicy::viewAny()` verifica `usuarios.viewAny`. O serviço pagina 15 registros, ordena por nome/id e filtra por `instituicao_id` quando o ator não é SuperAdmin. Na arquitectura multi-tenant, a base local já delimita a instituição/tenant; o filtro também aparece no serviço.

Cada linha recebe flags `can.update`, `can.delete` e `can.manage_permissions`, usadas para mostrar/ocultar os botões. Essas flags são apenas apresentação: as rotas voltam a autorizar no servidor.

### Criar

`StoreUserRequest` exige nome, e-mail único, password de pelo menos 8 caracteres; telefone é opcional e `roles` é opcional/array. Cada role é limitada por allowlist do guard `tenant`.

O controller define `instituicao_id` usando o ator autenticado, não o payload. `CreateUser` executa numa transação:

1. separa `roles` e password em texto para a notificação;
2. cria o usuário;
3. executa `syncRoles()` com as roles recebidas ou `[]`;
4. chama `UserRoleProfileService`;
5. envia notificação de credenciais quando existe password;
6. devolve o usuário com roles carregadas.

#### Criar com uma role ou várias

Todas as roles enviadas são sincronizadas ao usuário. As permissões dessas roles passam a ser herdadas. Se uma das roles for `Professor`, `UserRoleProfileService` cria o registro em `professores` se ainda não existir.

#### Criar sem role

É válido: `roles` é nullable e o action usa lista vazia. O usuário é criado sem roles. Porém, as rotas internas do dashboard exigem uma role no middleware. O `/dashboard` está fora desse middleware, e o controller actual encaminha qualquer usuário que não corresponda aos casos conhecidos para o dashboard de Director. Esse fallback torna a conta sem role um caso de negócio mal definido e potencialmente confuso.

#### Restrição de roles por ator

- A UI consulta `UserManagementService::roles()` e exclui `SuperAdmin`, `Aluno` e `Candidato` por padrão.
- Para Subdirector, a UI também exclui `Director` e `Subdirector`.
- `StoreUserRequest` exclui `SuperAdmin` e `Candidato`; para Subdirector exclui também `Director`, `Subdirector` e `Aluno`.
- A allowlist da request e a lista da UI não coincidem totalmente: `Aluno` é filtrado pelo serviço da UI, mas a request de criação permite esse nome para atores que não sejam Subdirector.
- Um usuário com role `Aluno` criado por este CRUD não recebe automaticamente registro `alunos`; esse vínculo pertence ao fluxo de matrícula/cadastro académico.
- Para atores não Subdirector, não há uma regra geral no request que limite a delegação de roles ao nível hierárquico do ator. A validação limita por nomes/guard, mas não define uma matriz completa de promoção.

### Editar perfil e roles

`UpdateUserRequest` autoriza o ator contra o usuário-alvo e valida nome/e-mail/telefone/password/roles. Password em branco é removida do payload e não altera a senha.

No action `UpdateUser`:

1. `roles` é extraída com fallback `[]`;
2. os restantes campos são actualizados;
3. `syncRoles($roles)` substitui completamente as roles anteriores;
4. `UserRoleProfileService` é chamado;
5. o usuário é devolvido com roles recarregadas.

Consequência: ao submeter `roles: []`, o usuário fica sem role. Mais importante, se um cliente enviar um update sem a chave `roles`, o fallback também vira `[]` e remove todas as roles. O formulário React actual envia sempre a lista, mas o endpoint permite esse caso.

`UserManagementService::roles($actor, $target)` mantém as roles actuais do alvo na lista para exibição/validação, mesmo se normalmente não forem atribuíveis pelo ator. Na edição, a UI também desactiva o seletor quando detecta role actual fora da lista permitida.

#### Perfil Professor

Adicionar `Professor` cria o perfil se ausente. Remover `Professor` não apaga nem desactiva o perfil `Professor`; a implementação actual só usa `firstOrCreate`, sem sincronização inversa. Pode permanecer registro de Professor para usuário que já não tem essa role.

#### Autoedição e Director

- Subdirector não pode actualizar o próprio perfil nem gerir as próprias permissões.
- Um usuário que não seja SuperAdmin não pode editar outro usuário que tenha role `Director`.
- SuperAdmin passa pela regra global `Gate::before`.
- A Policy verifica permissions e instituição; a UI também desactiva alguns controles, mas a Policy/request é a barreira efectiva.

### Remover

`UserPolicy::delete()` requer `usuarios.delete`, `usuarios.gerir`, não ser o próprio usuário e respeitar as proteções de Director/Subdirector/instituição.

`DeleteUser` limpa pivots de roles e permissões directas numa transação e chama `$user->delete()`. O modelo User não usa `SoftDeletes`, portanto trata-se de remoção física pelo Eloquent, sujeita às foreign keys/cascades da base de dados. A regra não exclui explicitamente alvos com role `Aluno`; a consequência sobre registros académicos depende das relações e constraints existentes.

## 4. Gerir permissões individuais

### O que a tela apresenta

`UserPermissionController::create()` carrega para o alvo:

- roles;
- permissões directas;
- permissões herdadas das roles.

O catálogo é calculado para o **ator**. Para Subdirector, `RoleManagementService::permissions()` remove permissões exclusivas do role Director, calculadas como `Director permissions - Subdirector permissions`. Os grupos são filtrados pela mesma lista delegável.

### O que acontece ao salvar

O formulário começa com directas + herdadas, filtradas para conter só permissões delegáveis pelo ator. No submit, remove do payload as permissões herdadas, pois são controladas pela role e não devem ser sincronizadas como directas.

`UpdateUserPermissionsRequest` exige autorização `managePermissions`, lista de strings, itens distintos e cada item na allowlist do ator. A acção sincroniza as permissões directas numa transação.

Para não apagar uma permissão directa antiga que o ator actual não pode gerir, `UpdateUserPermissions` preserva as permissões já atribuídas ao alvo que estejam fora do conjunto delegável. Registra em log o ator, alvo, instituição e diff adicionado/removido.

### Director

O Director recebe o catálogo tenant completo, desde que o catálogo/seeder esteja actualizado, e pode gerir permissões de usuários autorizados da instituição. Não pode gerir outro Director; SuperAdmin tem bypass global. A Policy permite gerir o próprio alvo se o ator tiver `usuarios.gerir`, embora a autoedição de Subdirector seja explicitamente bloqueada.

Permissão directa pode ampliar a capacidade efectiva do usuário sem alterar role. Isso é diferente de colocar o usuário numa role e precisa ser considerado ao definir delegação e auditoria.

### Subdirector

Subdirector recebe catálogo reduzido e não pode alterar a si próprio ou o Director. Pode gerir usuários autorizados da instituição com `usuarios.gerir`. Permissões directas antigas fora do seu alcance ficam preservadas; ele não as pode retirar nem adicionar novamente.

### Usuário sem role

Pode ser alvo da tela individual, se o ator passar em `managePermissions`. Permissões directas podem ser gravadas, mas isso não lhe dá uma role e não satisfaz middleware que exige papel. Logo, “sem role + permission directa” não é equivalente a “usuário funcional no dashboard”.

## 5. Roles e caminho legado

`RoleController` gere definição de roles: lista, cria, edita, sincroniza permissões da role e apaga. `RolePolicy` usa `acessos.viewAny` e `acessos.create`; a criação/actualização limita permissões com `RoleManagementService::permissions($actor)`.

Separadamente, `AccessManagementController::store()` sincroniza roles e permissões directas do usuário na mesma operação. É uma segunda superfície com request e validação próprias. Mesmo estando protegida por `managePermissions`, sua existência duplica a função da edição de roles e da tela “Gerir permissões”. É necessário decidir se esse endpoint ainda é usado ou se deve ser descontinuado para haver uma única regra de negócio.

## 6. Implementação e dados relacionados

- Users e roles/permissões usam guard `tenant` e base de dados do tenant.
- `CreateUser`/`UpdateUser` usam transação para os dados principais, roles e criação do perfil Professor.
- `UpdateUserPermissions` usa transação própria e log estruturado.
- `UserManagementService::index()` carrega roles, permissões das roles e directas para evitar N+1 ao montar a listagem.
- `RoleManagementService::permissions()` consulta as permissões existentes no tenant, mas `groupedPermissions()` usa uma lista de grupos definida no código. Uma permission nova pode existir no catálogo e não aparecer na interface agrupada até o mapa ser actualizado.
- `PermissionSeeder` define catálogo/labels; `RolePermissionSeeder` associa permissions aos papéis.
- Mudanças nos seeders não alteram tenants já existentes até que o seeder seja executado neles; dados antigos podem divergir do código actual.

## 7. Cobertura de testes actual

Encontrado no repositório:

- `TenantUserCreationTest`: exercita o action de criação de Professor, criação do perfil e notificação; não cobre as rotas completas do CRUD.
- `TenantUserPermissionsTest`: exercita a acção de permissões directas e o formato das permissões agrupadas para Subdirector.
- `UserManagementPolicyTest`: cobre partes da Policy, incluindo instituição, autoalteração e gestão de permissões.

Não encontrei uma matriz completa de testes HTTP para criar/editar/remover por ator, com múltiplas roles, role vazia, tentativa de atribuir role protegida, combinação de roles e falha de autorização. A validação actual do módulo é, portanto, parcial.

## 8. Decisões de negócio para discussão

1. Usuário sem role pode ser criado? Se sim, deve ficar pendente/inactivo até receber role, em vez de cair no dashboard genérico.
2. `roles` omitido numa actualização significa “manter roles” ou “remover todas”? O código actual interpreta como remover todas.
3. Cadastro de `Aluno` deve ser permitido no CRUD genérico ou exclusivamente no fluxo académico que cria o registro `Aluno`?
4. Quem pode criar/atribuir `Director`, `Subdirector`, `Secretaria`, `Professor` e múltiplas roles? A regra actual é uma allowlist parcial, não uma matriz hierárquica formal.
5. Remover role `Professor` deve apagar, arquivar ou manter o perfil de Professor?
6. Um usuário com role `Aluno` pode ser removido pelo CRUD de usuários? Como ficam matrícula, notas, histórico e vínculos?
7. Permissões directas são exceção administrativa ou parte oficial do modelo? Quem pode conceder permissões que superem a role do alvo?
8. A tela/endpoint legado `/access-management` continua necessário ou deve ser removido em favor de fluxos únicos?
9. A remoção do usuário deve ser física, desactivação lógica ou desactivação de acesso com preservação do histórico escolar?

## 9. Referências principais

- [UserController.php](../app/Http/Controllers/Tenant/UserController.php)
- [UserPermissionController.php](../app/Http/Controllers/Tenant/UserPermissionController.php)
- [UserPolicy.php](../app/Policies/Tenant/UserPolicy.php)
- [StoreUserRequest.php](../app/Http/Requests/Tenant/User/StoreUserRequest.php)
- [UpdateUserRequest.php](../app/Http/Requests/Tenant/User/UpdateUserRequest.php)
- [UpdateUserPermissionsRequest.php](../app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php)
- [UserManagementService.php](../app/Services/Tenant/Users/UserManagementService.php)
- [UserRoleProfileService.php](../app/Services/Tenant/Users/UserRoleProfileService.php)
- [RoleManagementService.php](../app/Services/Tenant/RoleManagementService.php)
- [RolePermissionSeeder.php](../database/seeders/Tenant/RolePermissionSeeder.php)
- [TenantUserCreationTest.php](../tests/Feature/TenantUserCreationTest.php)
- [TenantUserPermissionsTest.php](../tests/Feature/TenantUserPermissionsTest.php)
- [UserManagementPolicyTest.php](../tests/Unit/Tenant/UserManagementPolicyTest.php)