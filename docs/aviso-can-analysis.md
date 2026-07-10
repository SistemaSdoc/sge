# Análise de autorização para Avisos

## Estado atual

- O `AvisoPolicy` existe e está no local correto (`app/Policies/AvisoPolicy.php`).
- O Laravel detecta policies automaticamente usando o nome do modelo e o diretório `Policies`, então o registration manual em `AuthServiceProvider` não é necessário aqui.
- As permissões são definidas via seeders e seguem um padrão aproximado `resource.action`.
- O controller `AvisoController` usa `authorize(...)` em cada ação, o que é seguro.
- A interface React recebe booleans `can` específicos para `create_aviso`, `edit_aviso` e `delete_aviso` e usa isso para renderizar botões/menus condicionalmente.

## O que está bom

- `authorize('viewAny', Aviso::class)` e as outras chamadas de autorização no controller fornecem uma camada de proteção no servidor.
- O uso de `user->can('avisos.create', Aviso::class)` para `create` é correto e compatível com o policy.
- O retorno de `can` para cada item da lista torna o front-end mais robusto e evita mostrar ações que o usuário não pode executar.
- A proteção de escopo por `instituicao_id` no policy `view`, `update` e `delete` é uma boa prática de multi-tenant / isolamento de dados.
- O `Gate::before` em `AppServiceProvider` para `SuperAdmin` é um padrão aceitável quando há um papel global que ignora outras regras.

## O que deve ser ajustado para tornar mais sustentável e escalável

### 1. Centralizar autorização de recurso usando `authorizeResource`

No `AvisoController`, você pode reduzir repetição e garantir consistência com:

```php
public function __construct()
{
    $this->authorizeResource(Aviso::class, 'aviso');
}
```

Isso mapeia automaticamente as ações do resource controller para os métodos do policy.

### 2. Usar requests autorizados para `store` e `update`

Atualmente só o `store` usa `AvisoRequest`, e ele `authorize()` retorna `true`.

- Crie um `StoreAvisoRequest` com `authorize()` validando `avisos.create`.
- Crie um `UpdateAvisoRequest` com `authorize()` validando `avisos.update` no modelo atual.

Isso deixa a autorização mais próxima da validação e melhora a segurança.

### 3. Evitar chamada indireta `user->can(...)` dentro do policy

O `AvisoPolicy::update()` e `delete()` fazem isto:

```php
return $user->can('avisos.update') && $aviso->instituicao_id === $user->instituicao_id;
```

Funciona, mas cria um acoplamento entre a autorização do policy e o sistema de permissões.

Sugestão mais limpa:

```php
return $user->hasPermissionTo('avisos.update')
    && $aviso->instituicao_id === $user->instituicao_id;
```

ou ainda:

```php
use Illuminate\Auth\Access\Response;

public function update(User $user, Aviso $aviso): Response
{
    return $user->hasPermissionTo('avisos.update') && $aviso->instituicao_id === $user->instituicao_id
        ? Response::allow()
        : Response::deny('Acesso negado ao aviso desta instituição.');
}
```

Isso torna a lógica mais explícita e mais fácil de manter.

### 4. Adicionar `can.view_aviso` se o clique da linha deve respeitar permissão

No componente `aviso-table.jsx`, a `<TableRow onClick={...}>` abre o detalhe para qualquer aviso listado.

Se você quer que apenas avisos visualizáveis sejam clicáveis, inclua a permissão de visualização no `can` do aviso:

```php
'can' => [
    'view_aviso' => $user->can('avisos.view', $aviso),
    'edit_aviso' => $user->can('avisos.update', $aviso),
    'delete_aviso' => $user->can('avisos.delete', $aviso),
],
```

E condicione o clique no front-end.

### 5. Padronizar nomes de permissões

Atualmente há permissão com `curso-tutelado.viewAny`, `cursoclasseturno.viewAny`, `grelha.viewAny`, `classeturnodisciplina.viewAny`.

Para escala e legibilidade, escolha uma convenção única:

- `avisos.viewAny`, `avisos.view`, `avisos.create`, `avisos.update`, `avisos.delete`
- `curso_tutelado.viewAny`, `curso_classe_turno.viewAny`, `grelha_curricular.viewAny`

Ou use a mesma forma em todas as resources (`snake_case` sem hífen ou `camelCase` consistente).

### 6. Internacionalização

O código atual tem textos e mensagens de validação em português direto no request e no front-end.

Para suportar múltiplos idiomas, mova:

- strings de validação para `resources/lang/{locale}/validation.php`
- labels e mensagens do front-end para uma solução de i18n ou props Inertia compartilhadas

Exemplo no PHP:

```php
'titulo.required' => __('validation.attributes.titulo.required'),
```

### 7. Não confundir validação de requisição com autorização de rota

Você já faz `authorize()` no controller, mas também é recomendável que a `FormRequest` trate a autorização quando for usada. Isso evita processamento desnecessário de validação em requisições não autorizadas.

## Segurança e consistência

- `authorize(...)` no controller garante que qualquer request ao `AvisoController` seja bloqueada se o usuário não tiver direito.
- `AvisoPolicy` isola regras de acesso a nível de recurso, o que é correto.
- `Gate::before` para `SuperAdmin` é uma prática comum e segura.
- A única cautela real é ter certeza de que `AvisoPolicy::viewAny()` e `AvisoPolicy::view()` são consistentes. Se o usuário pode ver a lista de avisos, ele também deve poder ver os avisos individuais, salvo cláusulas de multi-tenant.

## Recomendações finais

1. Mantenha o `AvisoPolicy` e o `authorize(...)` no controller.
2. Adote `authorizeResource(Aviso::class, 'aviso')` para reduzir boilerplate.
3. Crie requests autorizados para store/update.
4. Padronize nomes de permissões em todos os seeders.
5. Use traduções em vez de strings fixas para texto de interface e validação.
6. Se quiser uma resposta mais rica, use `Illuminate\Auth\Access\Response` nos métodos do policy.

## Conclusão

A implementação atual está funcional e razoavelmente consistente. A principal melhoria para sustentabilidade é reduzir repetições e colocar a autorização em uma camada mais declarativa, usando recursos nativos do Laravel (`authorizeResource`, `FormRequest::authorize()`, `Response`), além de padronizar nomes de permissões e preparar i18n.
