# Proposta de implementação: labels amigáveis para permissões

## Contexto

O projeto usa Spatie Permission e, hoje, as permissões são exibidas em bruto no frontend com os valores técnicos do campo `name`, por exemplo:

- `users.view`
- `users.create`
- `roles.update`
- `permissions.assign`

Isso funciona para a lógica de autorização, mas não é amigável para um utilizador final que está a gerir roles e acessos.

A proposta abaixo mantém a autorização técnica intacta e separa a parte de apresentação.

---

## O que a pesquisa confirmou

### 1) Spatie Permission conserva o `name` como identificador técnico

A documentação oficial do package confirma que o valor principal da permissão continua a ser o campo `name`, usado para autorizações e verificações com `@can`, `can()`, `syncPermissions()`, etc.

Em outras palavras:

- o `name` deve continuar a ser o valor real e estável
- o texto amigável deve ser uma camada adicional na aplicação

### 2) O package não traz um `label` nativo para UI

A documentação pesquisada no Context7 não mostra um padrão oficial do package para `display_name`, `label` ou `friendly_name` como propriedade padrão da permissão. O comportamento recomendado é: usar o nome técnico para autorização e adaptar a UI com um mapeamento de exibição.

### 3) O projeto já usa esse padrão de “valor técnico + UI legível” em vários pontos

No código atual, a app já trabalha com valores como `name` para roles e permissões, e a UI mostra esses valores diretamente. Esse é o ponto onde a melhoria deve entrar: transformar a apresentação sem mexer na lógica.

---

## Conclusão técnica

A melhor solução para este projeto é:

- manter `name` como identificador técnico
- criar um mecanismo centralizado para converter esse `name` em um `label` amigável
- usar esse label apenas na UI de gestão de acessos e funções
- manter o envio/armazenamento real no backend inalterado

Essa abordagem é a mais segura, estável e alinhada com o que o Spatie Permission espera.

---

## Recomendação de implementação

### Opção recomendada: helper central de labels

Criar um helper/service, por exemplo:

- `app/Support/PermissionLabelMap.php`
- ou `app/Services/Tenant/PermissionLabelService.php`

Este serviço devolve um mapa de:

```php
[
    'users.view' => 'Ver utilizadores',
    'users.create' => 'Criar utilizadores',
    'roles.manage' => 'Gerir funções',
    'courses.view' => 'Ver cursos',
]
```

Depois, quando a aplicação precisa listar permissões:

```php
$permissions = Permission::query()
    ->where('guard_name', 'tenant')
    ->orderBy('name')
    ->get()
    ->map(fn ($permission) => [
        'value' => $permission->name,
        'label' => PermissionLabelMap::label($permission->name),
    ]);
```

### Vantagens

- mantém autorização e dados do sistema intactos
- evita migração de schema desnecessária
- reduz duplicação de texto em vários componentes
- é fácil de testar
- facilita manutenção futura

### Desvantagem

- depende de um mapa centralizado, que precisa ser mantido quando surgirem novas permissões

Como o projecto já tem um conjunto finito de permissões por módulo, isso é aceitável e bem administrável.

---

## Alternativa possível: coluna `label` na base de dados

Outra opção seria adicionar uma coluna `label` às permissões ou roles e guardar os textos amigáveis diretamente na base de dados.

Exemplo:

```php
Permission::create([
    'name' => 'users.view',
    'label' => 'Ver utilizadores',
    'guard_name' => 'tenant',
]);
```

### Quando fazer esta opção

- se houver necessidade real de editar labels pela UI
- se as permissões forem muito dinâmicas
- se houver vários clientes/tenants com labels diferentes

### Quando não recomendarmos esta opção agora

- porque exige migração
- porque muda a estrutura do package e adiciona mais complexidade
- porque não é necessário para a primeira entrega

Em termos práticos, para a funcionalidade atual, a solução de map centralizado é melhor e mais barata.

---

## Onde este ajuste funciona melhor no projeto

Os pontos principais que hoje mostram permissões em texto técnico são:

- [app/Services/Tenant/RoleManagementService.php](app/Services/Tenant/RoleManagementService.php)
- [app/Http/Controllers/Tenant/AccessManagementController.php](app/Http/Controllers/Tenant/AccessManagementController.php)
- [resources/js/pages/tenant/roles/components/role-form.jsx](resources/js/pages/tenant/roles/components/role-form.jsx)
- [resources/js/pages/tenant/gestao-acessos/components/drawer/permission-section.jsx](resources/js/pages/tenant/gestao-acessos/components/drawer/permission-section.jsx)

Esses locais são os candidatos certos para a adaptação.

---

## Estrutura proposta de implementação

### Backend

#### 1) Criar um helper de labels

Arquivo sugerido:

- `app/Support/PermissionLabelMap.php`

Responsabilidade:

- mapear `name` => `label`
- converter arrays de permissões para itens de UI

#### 2) Ajustar o service que devolve permissões

No `RoleManagementService`, em vez de devolver apenas strings, devolver objetos/arrays com:

```php
[
    'value' => 'users.view',
    'label' => 'Ver utilizadores',
]
```

#### 3) Ajustar o controller de gestão de acessos

No `AccessManagementController`, manter:

- `roles` e `directPermissions` como valores técnicos para o backend
- mas devolver `label` para a UI de apresentação

---

### Frontend

#### 1) Renderizar label em vez de name

Nos componentes que hoje mostram permissões:

- `RoleForm`
- `PermissionSection`

fazer:

```jsx
{permissions.map((permission) => (
  <label key={permission.value}>
    <Checkbox checked={...} />
    <span>{permission.label}</span>
  </label>
))}
```

#### 2) Continuar a enviar o valor técnico no submit

Quando a checkbox for selecionada, o payload continua a ter `permission.value`, isto é, o `name` da permissão. A label é só de apresentação.

---

## Exemplo de resultado final para a UI

Em vez de:

- `users.view`
- `users.create`
- `roles.update`

mostrar:

- Ver utilizadores
- Criar utilizadores
- Gerir funções

Isso deixa a gestão de acessos muito mais clara sem mexer na autorização real.

---

## Testes propostos

Como este ajuste é de apresentação e tradução, os testes mais úteis seriam:

### Backend

- helper retorna label correto para permissão conhecida
- helper retorna o nome técnico em fallback quando não existir mapeamento
- service devolve lista em formato `value + label`

### Frontend

- checkbox renderiza o label amigável em vez do `name`
- seleção continua a enviar o valor técnico correto ao backend

---

## Recomendação final

A recomendação é implementar a solução de labels amigáveis via helper central e mantendo o `name` técnico como fonte de verdade.

Esta solução é:

- compatível com Spatie Permission
- alinhada com as convenções do projeto
- simples de manter
- fácil de testar
- sem risco de quebrar permissões existentes

---

## Decisão

A implementação deve seguir este fluxo:

1. criar mapa central de labels
2. adaptar o service/controller para devolver estrutura com `value` e `label`
3. ajustar a UI para mostrar apenas `label`
4. validar com testes focados em gestão de acessos

Este é o caminho mais sólido para a próxima fase.
