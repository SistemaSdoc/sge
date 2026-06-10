# Paginação de Dados em Inertia.js - Guia Escalável

## 📊 Situação Atual

### Controller (Turnos)
```php
class TurnoController extends Controller
{
    public function index()
    {
        // ❌ PROBLEMA: Carrega TODOS os turnos na memória
        $turnos = Turno::all();

        return Inertia('turnos/index', [
            'turnos' => $turnos,
        ]);
    }
}
```

### Componente Index
```jsx
// Recebe todos os turnos de uma vez (não escalável)
export default function Index({ turnos }) {
  return <TurnoTable turnos={turnos} />;
}
```

### ⚠️ Problemas com este Padrão
1. **Performance**: Carrega TODOS os registros do banco
2. **Memória**: Não é escalável (1000+ registros = problema)
3. **UX**: Sem feedback de carregamento
4. **Flexibilidade**: Sem filtros, buscas ou ordenação

---

## ✅ Solução: 3 Padrões de Paginação Escalável

### 1️⃣ PAGINAÇÃO TRADICIONAL (Com controles)
**Quando usar:** Listas administrativas, dashboards, tabelas estruturadas

#### Backend - TurnoController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Paginar com 15 items por página
        $perPage = $request->input('per_page', 15);
        $turnos = Turno::paginate($perPage);

        return Inertia::render('turnos/index', [
            'turnos' => $turnos,
            // Filtros e metadados opcionais
            'filters' => [
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    // ... resto do CRUD
}
```

#### Frontend - turnos/index.jsx
```jsx
import { router, usePage } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import { PaginationControls } from '@/components/pagination-controls';
import { SearchInput } from '@/components/search-input';
import { useState } from 'react';

export default function Index({ turnos, filters }) {
  const [search, setSearch] = useState(filters.search || '');

  const handleSearch = (value) => {
    setSearch(value);
    // Volta para página 1 ao buscar
    router.visit('/turnos', {
      data: { search: value },
      preserveScroll: true,
    });
  };

  const handlePageChange = (page) => {
    router.visit('/turnos', {
      data: { page, search },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6 space-y-4">
      {/* Search */}
      <SearchInput 
        value={search}
        onChange={handleSearch}
        placeholder="Buscar turnos..."
      />

      {/* Table */}
      <TurnoTable turnos={turnos.data} />

      {/* Pagination Controls */}
      <PaginationControls 
        current={turnos.current_page}
        total={turnos.last_page}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
```

#### Componente Reutilizável - PaginationControls.jsx
```jsx
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export function PaginationControls({ current, total, onPageChange }) {
  return (
    <div className="flex items-center justify-between">
      <span className="text-sm text-muted-foreground">
        Página {current} de {total}
      </span>
      
      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={current === 1}
          onClick={() => onPageChange(current - 1)}
        >
          <ChevronLeft className="size-4" />
        </Button>

        {/* Números de página */}
        {Array.from({ length: Math.min(5, total) }, (_, i) => {
          let page;
          if (total <= 5) {
            page = i + 1;
          } else if (current <= 3) {
            page = i + 1;
          } else if (current >= total - 2) {
            page = total - 4 + i;
          } else {
            page = current - 2 + i;
          }

          return (
            <Button
              key={page}
              variant={current === page ? 'default' : 'outline'}
              size="sm"
              onClick={() => onPageChange(page)}
            >
              {page}
            </Button>
          );
        })}

        <Button
          variant="outline"
          size="sm"
          disabled={current === total}
          onClick={() => onPageChange(current + 1)}
        >
          <ChevronRight className="size-4" />
        </Button>
      </div>
    </div>
  );
}
```

#### Dados da Resposta (Structure)
```javascript
// turnos prop contém:
{
  data: [
    { id: 1, nome: 'Manhã' },
    { id: 2, nome: 'Tarde' },
    // ... 15 items
  ],
  current_page: 1,
  first_page_url: 'http://app.test/turnos?page=1',
  from: 1,
  last_page: 5,
  last_page_url: 'http://app.test/turnos?page=5',
  next_page_url: 'http://app.test/turnos?page=2',
  path: 'http://app.test/turnos',
  per_page: 15,
  prev_page_url: null,
  to: 15,
  total: 75
}
```

---

### 2️⃣ INFINITE SCROLL (Auto-carregamento)
**Quando usar:** Social feeds, listas de eventos, streams de dados

#### Backend - TurnoController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function index()
    {
        return Inertia::render('turnos/index', [
            // ✅ Use Inertia::scroll() para infinite scroll
            'turnos' => Inertia::scroll(fn () => Turno::paginate(20)),
        ]);
    }
}
```

#### Frontend - turnos/index.jsx
```jsx
import { InfiniteScroll } from '@inertiajs/react';
import { TurnoCard } from './components/turno-card';
import { Loader2 } from 'lucide-react';

export default function Index({ turnos }) {
  return (
    <InfiniteScroll
      data="turnos"  // Prop que contém paginação
      hasMore={turnos.has_more_pages}
      isPreviousData={false}
    >
      <div className="mx-auto w-full max-w-7xl p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {turnos.data.map((turno) => (
            <TurnoCard key={turno.id} turno={turno} />
          ))}
        </div>

        {/* Loader ao carregar mais */}
        <div className="flex justify-center py-8">
          {turnos.has_more_pages && (
            <>
              <Loader2 className="animate-spin" />
              <span className="ml-2">Carregando...</span>
            </>
          )}
        </div>
      </div>
    </InfiniteScroll>
  );
}
```

#### TurnoCard.jsx
```jsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export function TurnoCard({ turno }) {
  return (
    <Card className="hover:shadow-lg transition-shadow">
      <CardHeader>
        <CardTitle>{turno.nome}</CardTitle>
      </CardHeader>
      <CardContent>
        <Badge>{turno.alunos_count || 0} alunos</Badge>
      </CardContent>
    </Card>
  );
}
```

---

### 3️⃣ DEFERRED + PARTIAL RELOAD (Otimizado)
**Quando usar:** Primeira carga rápida, depois carrega dados sob demanda

#### Backend - TurnoController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Inertia\Inertia;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        return Inertia::render('turnos/index', [
            // ✅ Carrega rápido (apenas contagem)
            'turnosCount' => Turno::count(),
            
            // ✅ Carrega depois sob demanda (partial reload)
            'turnos' => Inertia::defer(
                fn() => Turno::paginate($perPage, page: $page)
            )->deepMerge(),
        ]);
    }
}
```

#### Frontend - turnos/index.jsx
```jsx
import { router, usePage } from '@inertiajs/react';
import { Deferred } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import { Skeleton } from '@/components/ui/skeleton';

export default function Index({ turnosCount, turnos }) {
  const handleLoadMore = () => {
    // Carrega a próxima página (partial reload)
    router.visit('/turnos', {
      data: { page: turnos.current_page + 1 },
      // ✅ Só recarrega o prop 'turnos', não toda página
      only: ['turnos'],
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      {/* Header - carrega rápido */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold">Turnos</h1>
        <p className="text-muted-foreground">
          Total: {turnosCount} turnos
        </p>
      </div>

      {/* Dados - carrega depois */}
      <Deferred data="turnos" fallback={<SkeletonTable />}>
        {({ reloading }) => (
          <div className={reloading ? 'opacity-50' : ''}>
            <TurnoTable turnos={turnos.data} />

            {/* Load more */}
            {turnos.current_page < turnos.last_page && (
              <button
                onClick={handleLoadMore}
                disabled={reloading}
                className="mt-4 px-4 py-2 bg-blue-500 text-white rounded"
              >
                {reloading ? 'Carregando...' : 'Carregar Mais'}
              </button>
            )}
          </div>
        )}
      </Deferred>
    </div>
  );
}

function SkeletonTable() {
  return (
    <div className="space-y-2">
      {Array.from({ length: 5 }).map((_, i) => (
        <Skeleton key={i} className="h-12 w-full" />
      ))}
    </div>
  );
}
```

---

## 🎯 Comparação das 3 Abordagens

| Aspecto | Tradicional | Infinite Scroll | Deferred |
|---------|------------|-----------------|---------|
| **Controle** | ✅ Usuário controla | ❌ Automático | ✅ Híbrido |
| **UX** | Boa | Ótima (mobile) | Ótima |
| **Performance** | Excelente | Ótima | Excelente |
| **Busca/Filtro** | ✅ Sim | ❌ Difícil | ✅ Sim |
| **Scroll to top** | ✅ Automático | ❌ Não | ✅ Sim |
| **SEO** | ✅ Bom | ❌ Ruim | ✅ Bom |
| **Complexidade** | Baixa | Média | Média |
| **Use case** | Admin, Tabelas | Feeds, Redes | Dashboard |

---

## 📋 Checklist de Implementação

### Para cada lista que não tem paginação:

- [ ] Escolher qual abordagem (tradicional/infinite/deferred)
- [ ] Implementar `.paginate()` no controller
- [ ] Passar dados paginados ao Inertia
- [ ] Criar componente de paginação (se tradicional)
- [ ] Testar com 100+ registros
- [ ] Adicionar indicador de carregamento
- [ ] Testar em dispositivos móveis
- [ ] Documentar em comentários

---

## 🔧 Implementação Gradual

### Passo 1: Refatorar TurnoController (Imediatamente)
```php
// Mudar de:
$turnos = Turno::all();

// Para:
$turnos = Turno::paginate(15);
```

### Passo 2: Atualizar TurnoTable
```jsx
// Recebia array simples
turnos.map(turno => ...)

// Agora recebe paginated object
turnos.data.map(turno => ...)
```

### Passo 3: Adicionar Controles de Paginação
- Botões next/prev
- Números de página
- Indicador "página X de Y"

### Passo 4: Melhorias (Opcional)
- Busca/filtro
- Ordenação
- Per-page selector

---

## 💾 Exemplo Completo: Turnos com Paginação

### 1. Backend - app/Http/Controllers/TurnoController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = Turno::query();

        // Busca
        if ($search) {
            $query->where('nome', 'like', "%{$search}%");
        }

        // Ordenação
        $query->orderBy('nome', 'asc');

        // Paginação
        $turnos = $query->paginate($perPage);

        return Inertia::render('turnos/index', [
            'turnos' => $turnos,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    // ... resto omitido
}
```

### 2. Frontend - resources/js/pages/turnos/index.jsx
```jsx
import { router } from '@inertiajs/react';
import { TurnoTable } from './components/turno-table';
import { PaginationControls } from '@/components/pagination-controls';
import { SearchInput } from '@/components/search-input';
import { useState } from 'react';

export default function Index({ turnos, filters }) {
  const [search, setSearch] = useState(filters.search);

  const handleSearch = (value) => {
    setSearch(value);
    router.visit('/turnos', {
      data: { search: value, page: 1 },
      preserveScroll: true,
    });
  };

  const handlePageChange = (page) => {
    router.visit('/turnos', {
      data: { page, search },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6 space-y-4">
      <SearchInput 
        value={search}
        onChange={handleSearch}
        placeholder="Buscar turno..."
      />

      <TurnoTable turnos={turnos.data} />

      <PaginationControls 
        current={turnos.current_page}
        total={turnos.last_page}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
```

### 3. Frontend - resources/js/pages/turnos/components/turno-table.jsx
```jsx
import { router } from '@inertiajs/react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ClockIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

export function TurnoTable({ turnos }) {
  if (!turnos?.length) {
    return (
      <EmptyState
        variant="table"
        icon={ClockIcon}
        title="Nenhum turno encontrado"
        description="Nenhum resultado coincide com sua busca"
      />
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Turnos</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="px-4">Nome</TableHead>
              <TableHead className="px-4 text-right">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {turnos.map((turno) => (
              <TableRow key={turno.id} className="hover:cursor-pointer">
                <TableCell className="px-4 font-medium">
                  {turno.nome}
                </TableCell>
                <TableCell className="px-4 text-right">
                  <Button 
                    variant="ghost" 
                    onClick={() => router.visit(`/turnos/${turno.id}`)}
                  >
                    Ver
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}
```

---

## 🎓 Referências Inertia.js v3

- [Pagination Docs](https://inertiajs.com/docs/v3/data-props/partial-reloads)
- [Infinite Scroll](https://inertiajs.com/docs/v3/data-props/infinite-scroll)
- [Deferred Props](https://inertiajs.com/docs/v3/data-props/deferred-props)
- [Merging Props](https://inertiajs.com/docs/v3/data-props/merging-props)

---

## 🚀 Próximos Passos

1. **Hoje**: Implementar paginação tradicional no TurnoController
2. **Amanhã**: Atualizar componentes similares (Instituições, Cursos, etc.)
3. **Semana**: Adicionar busca/filtro a todas as listas
4. **Futuro**: Considerar Infinite Scroll para feeds

---

## 💡 Dicas de Performance

```php
// ✅ BOM: Lazy loading com count
return Inertia::render('index', [
    'count' => Model::count(),
    'items' => Inertia::defer(fn() => Model::paginate()),
]);

// ✅ BOM: Query optimization
$items = Model::select(['id', 'name', 'created_at'])
    ->with('relation:id,name')
    ->paginate(15);

// ❌ RUIM: N+1 problem
$items = Model::all(); // Carrega TUDO

// ❌ RUIM: Sem índices no banco
->where('email', 'like', "%{$search}%");
// Adicionar índice fulltext: ->whereFullText(['email'], $search)
```

