# Menu Lateral — Documentação

O menu lateral é gerado e filtrado **no servidor** com base nas Policies do Laravel.
O frontend apenas itera e renderiza — sem nenhuma lógica de permissão no client.

## Como funciona

```
SidebarMenuService::build()
  → define todos os itens do menu
  → avalia cada can: via Gate::allows()
  → remove itens e grupos que o utilizador não pode ver
  → devolve array limpo

HandleInertiaRequests::share()
  → injeta o resultado como prop "sidebar" em todas as páginas

AppSidebar.tsx
  → lê usePage<SharedProps>().props.sidebar
  → passa para <NavMain groups={sidebar} />

NavMain.tsx
  → itera os grupos e itens
  → resolve o ícone via resolveIcon(item.icon)
  → renderiza o Link
```

## Estrutura das classes PHP

### `MenuItem`

Representa um item individual do menu.

```php
new MenuItem(
    key:   'turmas',          // identificador único (kebab-case)
    title: 'Turmas',          // label visível no menu
    href:  action([TurmaController::class, 'index']), // URL resolvida via controller
    icon:  'Users',           // nome do ícone (tem de existir no iconRegistry)
    can:   fn () => Gate::allows('viewAny', Turma::class), // regra de visibilidade
)
```


| Propriedade | Tipo           | Descrição                                                     |
| ----------- | -------------- | --------------------------------------------------------------- |
| `key`       | `string`       | Identificador único. Usado como React key.                     |
| `title`     | `string`       | Texto exibido no menu.                                          |
| `href`      | `string`       | URL gerada via`action([Controller::class, 'method'])`.          |
| `icon`      | `string`       | Nome do ícone Lucide. Tem de estar registado no`iconRegistry`. |
| `can`       | `Closure|bool` | `true`= visível para todos.`Closure`= avalia a Policy.         |

### `MenuGroup`

Agrupa itens sob um label.
**Se todos os itens do grupo forem filtrados, o grupo desaparece automaticamente.**

```php
new MenuGroup('Plataforma', [
    new MenuItem(...),
    new MenuItem(...),
])
```

## Como adicionar um item

### 1. Criar a rota e o controller (se não existir)

```php
// routes/web.php
Route::resource('relatorios', RelatorioController::class);
```

### 2. Adicionar o item no `SidebarMenuService.php`

```php
new MenuItem(
    key:   'relatorios',
    title: 'Relatórios',
    href:  action([RelatorioController::class, 'index']),
    icon:  'BarChart',
    can:   fn () => Gate::allows('viewAny', Relatorio::class),
),
```

### 3. Registar o ícone no `iconRegistry`

Em `resources/js/lib/icon-registry.ts`:

```ts
import { BarChart } from 'lucide-react'; // 1. importar do lucide

export const iconRegistry: Record<string, LucideIcon> = {
  // ...ícones existentes...
  BarChart, // 2. adicionar ao registry
};
```

> **Dica:** Verifica se o ícone existe em https://lucide.dev antes de usar.
> Se adicionares no backend mas esqueceres o registry, o sistema lança um erro em dev com instruções.

## Como remover um item

Basta apagar (ou comentar) o `new MenuItem(...)` correspondente no `SidebarMenuService.php`.
O frontend não precisa de alterações.

```php
// new MenuItem(
//     key:   'grupos-pap',
//     title: 'Grupos PAP',
//     ...
// ),
```

## Como filtrar por Policy

### Item visível para toda a gente

```php
can: true,
```

### Item visível só para SuperAdmin

O SuperAdmin é tratado globalmente via `Gate::before()` no `AppServiceProvider`.
Basta que a Policy do model devolva `false` para todos os outros roles — o SuperAdmin passa sempre.

```php
can: fn () => Gate::allows('viewAny', Relatorio::class),
```

### Item visível apenas para utilizadores com instituição (ex: Director, Subdirector)

```php
can: function () {
    $user = Auth::user();

    if (!$user?->instituicao_id) return false;

    $instituicao = Instituicao::find($user->instituicao_id);

    return $instituicao && Gate::allows('view', $instituicao);
},
```

### Item com Policy ainda não implementada (temporário)

```php
can: true, // TODO: RelatorioPolicy
```

Marca sempre com `TODO` para não ficarem esquecidos.

## Como adicionar um novo grupo

```php
new MenuGroup('Financeiro', [
    new MenuItem(
        key:   'pagamentos',
        title: 'Pagamentos',
        href:  action([PagamentoController::class, 'index']),
        icon:  'CreditCard',
        can:   fn () => Gate::allows('viewAny', Pagamento::class),
    ),
]),
```

O grupo aparece no menu apenas se pelo menos um item for visível para o utilizador.

## Ícones disponíveis

Ícones actualmente registados em `resources/js/lib/icon-registry.ts`:


| Nome (backend)  | Ícone                           |
| --------------- | -------------------------------- |
| `Bell`          | Sino — Avisos                   |
| `BookOpen`      | Livro — Cursos                  |
| `Building2`     | Edifício — Instituições      |
| `ClipboardList` | Lista — Inscrições            |
| `Clock4`        | Relógio — Turnos               |
| `FileText`      | Ficheiro — Pautas               |
| `GraduationCap` | Licenciatura — Classes / Alunos |
| `LayoutGrid`    | Grelha — Dashboard              |
| `ShieldCheck`   | Escudo — Acessos                |
| `Users`         | Pessoas — Turmas / Professores  |

Para adicionar um novo, segue o passo 3 da secção [Como adicionar um item](https://claude.ai/chat/8972ea9b-dad7-4c2d-9e35-d870ab7cceaa#como-adicionar-um-item).

## Regras gerais

* **Nunca** colocar lógica de permissão no frontend. Toda a filtragem é feita no `SidebarMenuService`.
* A string do `icon` no backend **tem de** bater com a chave no `iconRegistry`. Em dev, um erro claro é lançado se não bater.
* Usar sempre `action([Controller::class, 'method'])` para os `href` — nunca strings hardcoded nem `route()` com nome de string solta.
* Itens sem Policy ainda devem ter o comentário `// TODO: NomePolicy` para rastreabilidade.
* O `key` de cada item deve ser único e em kebab-case.

## Ficheiros relevantes


| Ficheiro                                        | Responsabilidade                                |
| ----------------------------------------------- | ----------------------------------------------- |
| `app/Services/Menu/SidebarMenuService.php`      | Define e filtra todos os itens do menu          |
| `app/Services/Menu/MenuItem.php`                | Estrutura de um item individual                 |
| `app/Services/Menu/MenuGroup.php`               | Estrutura de um grupo de itens                  |
| `app/Http/Middleware/HandleInertiaRequests.php` | Injeta`sidebar`nos shared props do Inertia      |
| `resources/js/lib/icon-registry.ts`             | Mapeia nomes de ícones para componentes Lucide |
| `resources/js/components/nav-main.tsx`          | Renderiza os grupos e itens                     |
| `resources/js/components/app-sidebar.tsx`       | Lê os props e monta a sidebar                  |
| `resources/js/types/global.d.ts`                | Tipos`NavItem`,`NavGroup`,`SharedProps`         |
