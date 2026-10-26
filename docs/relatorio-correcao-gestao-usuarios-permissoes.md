# Relatório de correção: gestão de usuários e permissões

Data: 2026-09-25  
Escopo: módulo tenant de usuários, roles, permissões individuais e gestão de acessos.

## Resultado executivo

Os principais bugs de autorização, seleção do usuário-alvo, delegação de permissões, isolamento por instituição e integração frontend foram corrigidos.

O módulo está significativamente mais seguro contra os problemas encontrados na análise inicial, mas não deve ser considerado totalmente auditado ou seguro em sentido absoluto. Ainda existem dois pontos residuais: a auditoria foi implementada em logs da aplicação, não em histórico persistente, e o `npm run types:check` continua bloqueado por uma configuração global inválida do TypeScript.

## Erros encontrados e soluções aplicadas

### 1. Usuário-alvo era substituído pelo usuário autenticado

**Erro:** `UserController::edit()` e `UserController::update()` recebiam o usuário da rota, mas sobrescreviam a variável com `Auth::guard('tenant')->user()`.

**Risco:** uma operação destinada ao usuário A poderia carregar ou alterar o usuário B, normalmente o próprio ator autenticado. A validação de autoedição também comparava o usuário consigo mesmo.

**Solução:** o alvo recebido pela rota foi preservado e o ator passou a usar a variável `$currentUser`. A comparação de autoedição agora é feita entre ator e alvo reais.

Arquivo: [app/Http/Controllers/Tenant/UserController.php](../app/Http/Controllers/Tenant/UserController.php)

### 2. Gestão de permissões usava a ability de edição cadastral

**Erro:** o frontend liberava `manage_permissions` usando `can('update', $user)`, confundindo editar dados com atribuir permissões.

**Risco:** um usuário autorizado a editar nome/e-mail poderia aparecer como autorizado a administrar acessos.

**Solução:** foi criada a ability `managePermissions` na `UserPolicy`. Ela verifica a permissão `usuarios.gerir`, instituição, proteção de Director e autoalteração de Subdirector. Controllers, service, request e frontend agora usam a mesma regra.

Arquivo: [app/Policies/Tenant/UserPolicy.php](../app/Policies/Tenant/UserPolicy.php)

### 3. Requests aceitavam operações sem autorização própria

**Erro:** requests de usuários, permissões, roles e gestão legada de acessos retornavam `true` em `authorize()`.

**Risco:** a proteção dependia apenas de chamadas manuais nos controllers e poderia ser perdida se a request fosse reutilizada em outro endpoint.

**Solução:** as requests passaram a resolver o ator no guard `tenant` e validar a ability correspondente contra o alvo ou role. A validação de permissões também rejeita duplicidades e limita os nomes ao catálogo permitido pelo ator.

Arquivos:

- [app/Http/Requests/Tenant/User/StoreUserRequest.php](../app/Http/Requests/Tenant/User/StoreUserRequest.php)
- [app/Http/Requests/Tenant/User/UpdateUserRequest.php](../app/Http/Requests/Tenant/User/UpdateUserRequest.php)
- [app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php](../app/Http/Requests/Tenant/User/UpdateUserPermissionsRequest.php)
- [app/Http/Requests/Tenant/Role/StoreRoleRequest.php](../app/Http/Requests/Tenant/Role/StoreRoleRequest.php)
- [app/Http/Requests/Tenant/Role/UpdateRoleRequest.php](../app/Http/Requests/Tenant/Role/UpdateRoleRequest.php)

### 4. Guard de autorização podia ser o errado

**Erro:** vários controllers tenant usavam `Gate::authorize()` sem indicar o usuário autenticado do guard `tenant`.

**Risco:** a autorização podia consultar o guard padrão, negar usuários válidos ou produzir comportamento inconsistente entre páginas e endpoints.

**Solução:** controllers de usuários, permissões, roles e acessos passaram a usar `Gate::forUser($actor)` com o usuário de `Auth::guard('tenant')`.

### 5. Nome da permissão de gestão era inconsistente

**Erro:** a policy consultava `usuarios.gerir`, mas o seeder concedia `utilizadores.gerir`.

**Risco:** roles semeados podiam não conseguir administrar usuários, ou dados antigos poderiam produzir permissões divergentes.

**Solução:** o catálogo e o seeder foram alinhados para `usuarios.gerir`, incluindo a permissão no catálogo tenant.

Arquivos:

- [database/seeders/Tenant/PermissionSeeder.php](../database/seeders/Tenant/PermissionSeeder.php)
- [database/seeders/Tenant/RolePermissionSeeder.php](../database/seeders/Tenant/RolePermissionSeeder.php)

### 6. Subdirector quebrava o agrupamento de permissões

**Erro:** `array_flip()` recebia as opções completas de permissão em vez dos valores string.

**Risco:** a tela agrupada podia ocultar permissões válidas ou gerar comportamento incorreto para Subdirector.

**Solução:** o serviço agora extrai `value` antes de aplicar `array_flip()`.

Arquivo: [app/Services/Tenant/RoleManagementService.php](../app/Services/Tenant/RoleManagementService.php)

### 7. Caminho legado de acessos não seguia a mesma segurança

**Erro:** `AccessManagementController` usava `update` em vez de `managePermissions` e aceitava roles/permissões sem limitar corretamente guard e delegação.

**Risco:** existiam dois caminhos com regras diferentes para alterar acessos.

**Solução:** o endpoint legado foi alinhado à ability de permissões individuais, às regras de instituição e ao conjunto de roles/permissões delegáveis.

Arquivo: [app/Http/Requests/Tenant/AccessManagement/StoreRoleAndPermissionRequest.php](../app/Http/Requests/Tenant/AccessManagement/StoreRoleAndPermissionRequest.php)

### 8. URL manual no frontend

**Erro:** a tabela montava `/dashboard/users/{id}/permissions` manualmente.

**Risco:** mudanças de prefixo, domínio tenant ou rota poderiam quebrar a navegação.

**Solução:** o link passou a usar a função Wayfinder gerada para `UserPermissionController::create`.

Arquivo: [resources/js/pages/tenant/users/components/user-table.jsx](../resources/js/pages/tenant/users/components/user-table.jsx)

### 9. Possível N+1 na listagem

**Erro:** a listagem carregava roles, mas consultava permissões diretas e herdadas durante a transformação de cada usuário.

**Risco:** aumento de consultas e degradação com muitos usuários.

**Solução:** roles, permissões diretas e permissões das roles passaram a ser eager loaded; a transformação usa as relações carregadas.

Arquivo: [app/Services/Tenant/Users/UserManagementService.php](../app/Services/Tenant/Users/UserManagementService.php)

### 10. Alteração de permissões sem transação ou rastreabilidade

**Erro:** `syncPermissions()` era executado isoladamente e sem registrar o diff.

**Risco:** falhas parciais e dificuldade para descobrir quem adicionou ou removeu acesso.

**Solução:** a operação passou a usar transação e gerar log estruturado com ator, alvo, instituição, permissões adicionadas e removidas.

Arquivo: [app/Actions/Tenant/User/UpdateUserPermissions.php](../app/Actions/Tenant/User/UpdateUserPermissions.php)

## Correções de testes e infraestrutura

- Criada a factory tenant que estava vazia: [database/factories/Tenant/UserFactory.php](../database/factories/Tenant/UserFactory.php).
- A autorização de perfil próprio e perfil académico foi centralizada na `UserPolicy`.
- Testes tenant passaram a usar as migrations tenant, evitando executar o model tenant contra a tabela central.
- Adicionados testes para gestão de permissões na mesma instituição.
- Adicionado teste para impedir Subdirector de alterar as próprias permissões.
- Corrigidos testes que esperavam `utilizadores.gerir` ou um nome de rota inexistente.

Testes validados:

- `TenantUserPermissionsTest`: passou.
- `TenantUserCreationTest`: passou.
- `UserManagementPolicyTest`: passou.
- Total validado no conjunto focado: **14 testes passaram**.

Também passaram:

- `vendor/bin/pint --dirty --format agent`
- `git diff --check`
- verificação de diagnósticos nos principais arquivos PHP/React.

## Segurança após a correção

### Proteções agora existentes

- O usuário-alvo não é mais perdido no controller.
- A operação de permissões exige `managePermissions`.
- O ator é resolvido pelo guard `tenant`.
- O isolamento por instituição é aplicado na policy.
- Director protegido e autoalteração de Subdirector são bloqueados.
- Roles e permissões fora da capacidade de delegação são rejeitados no servidor.
- O frontend não é a fonte de autorização; o backend valida novamente.
- A consulta e atualização do perfil próprio usam abilities da `UserPolicy`, incluindo a regra de perfil académico de aluno.
- A alteração de permissões é transacional.
- A tabela de usuários usa permissões específicas para informar ações disponíveis.

### Limitações residuais

1. **Auditoria não persistente:** o histórico está em logs da aplicação. Para compliance ou investigação completa, deve ser criada uma tabela de auditoria imutável com ator, alvo, tenant, diff, data e motivo.
2. **Dados antigos:** se existirem permissões persistidas com o nome `utilizadores.gerir`, é necessária uma migração de dados para `usuarios.gerir`.
3. **TypeScript:** `npm run types:check` continua bloqueado por `ignoreDeprecations: "6.0"` em [tsconfig.json](../tsconfig.json), valor incompatível com o TypeScript instalado. Isso impede declarar o frontend completamente validado por typecheck.
4. **Teste HTTP com tenancy real:** os testes focados validam policy, action e persistência no schema tenant, mas o ambiente de teste não inicializa o domínio real do tenant. Recomenda-se adicionar um teste HTTP com tenant/domain configurado.
5. **Segurança operacional:** é necessário executar os seeders/migração de dados em cada tenant existente e limpar o cache de permissões após o deploy.

## Conclusão

Para os bugs identificados no relatório original, a correção aplicada resolve as causas principais e reduz substancialmente o risco de alteração indevida de usuários e permissões. O módulo está protegido no backend contra os cenários testados, mas a segurança completa ainda depende da migração de dados antigos, da correção do typecheck, de um teste HTTP com tenancy real e, caso seja necessário histórico de compliance, de auditoria persistente.