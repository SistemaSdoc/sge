# CRUD com Inertia + React + Wayfinder

Guia completo com exemplos de `<Form>` e `useForm()` para todas operações.

---

## 📋 Índice

1. [CREATE](#create)
2. [READ](#read)
3. [UPDATE](#update)
4. [DELETE](#delete)
5. [Wayfinder URLs](#wayfinder-urls)

---

## CREATE

### Opção 1: `<Form>` (Recomendado para forms simples)

```jsx
// pages/classes/create.jsx
import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ClasseController';

export default function Create() {
  return (
    <>
      <Head title="Nova Classe" />
      <Form {...store.form()} resetOnSuccess>
        {({ errors, processing }) => (
          <form onSubmit={(e) => { e.preventDefault(); }}>
            <input
              type="text"
              name="nome"
              placeholder="Nome"
            />
            {errors.nome && <span className="text-red-500">{errors.nome}</span>}

            <button type="submit" disabled={processing}>
              {processing ? 'Criando...' : 'Criar'}
            </button>
          </form>
        )}
      </Form>
    </>
  );
}
```

### Opção 2: `useForm()` (Mais controle)

```jsx
// pages/classes/create.jsx
import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/ClasseController';

export default function Create() {
  const form = useForm({
    nome: '',
    ordem: 0,
  });

  const handleSubmit = (e) => {
    e.preventDefault();

    form.post(store.url(), {
      onSuccess: () => {
        console.log('Classe criada!');
        form.reset();
      },
      onError: () => {
        console.log('Erro ao criar');
      },
    });
  };

  return (
    <>
      <Head title="Nova Classe" />
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          value={form.data.nome}
          onChange={(e) => form.setData('nome', e.target.value)}
          placeholder="Nome"
          disabled={form.processing}
        />
        {form.errors.nome && (
          <span className="text-red-500">{form.errors.nome}</span>
        )}

        <input
          type="number"
          value={form.data.ordem}
          onChange={(e) => form.setData('ordem', parseInt(e.target.value))}
          placeholder="Ordem"
          disabled={form.processing}
        />
        {form.errors.ordem && (
          <span className="text-red-500">{form.errors.ordem}</span>
        )}

        <button type="submit" disabled={form.processing}>
          {form.processing ? 'Criando...' : 'Criar'}
        </button>
      </form>
    </>
  );
}
```

---

## READ

### Listar com Inertia Props

```jsx
// pages/classes/index.jsx
import { usePage, Head } from '@inertiajs/react';
import { ClasseTable } from '@/components/classes/classe-table';

export default function Index() {
  const { classes } = usePage().props;

  return (
    <>
      <Head title="Classes" />
      <ClasseTable classes={classes ?? []} />
    </>
  );
}
```

### Visualizar Item

```jsx
// pages/classes/show.jsx
import { usePage, Head } from '@inertiajs/react';

export default function Show() {
  const { classe } = usePage().props;

  return (
    <>
      <Head title={classe.nome} />
      <div>
        <h1>{classe.nome}</h1>
        <p>Ordem: {classe.ordem}</p>
      </div>
    </>
  );
}
```

---

## UPDATE

### Opção 1: `<Form>` (Recomendado)

```jsx
// pages/classes/edit.jsx
import { Head, usePage } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/ClasseController';

export default function Edit() {
  const { classe } = usePage().props;

  return (
    <>
      <Head title={`Editar ${classe.nome}`} />
      <Form 
        method="put"
        action={update.url(classe.id)}
      >
        {({ errors, processing }) => (
          <form>
            <input
              type="text"
              name="nome"
              defaultValue={classe.nome}
            />
            {errors.nome && (
              <span className="text-red-500">{errors.nome}</span>
            )}

            <input
              type="number"
              name="ordem"
              defaultValue={classe.ordem}
            />
            {errors.ordem && (
              <span className="text-red-500">{errors.ordem}</span>
            )}

            <button type="submit" disabled={processing}>
              {processing ? 'Atualizando...' : 'Atualizar'}
            </button>
          </form>
        )}
      </Form>
    </>
  );
}
```

### Opção 2: `useForm()` (Mais controle)

```jsx
// pages/classes/edit.jsx
import { Head, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { update } from '@/actions/App/Http/Controllers/ClasseController';

export default function Edit() {
  const { classe } = usePage().props;
  
  const form = useForm({
    nome: '',
    ordem: 0,
  });

  // Carregar dados iniciais
  useEffect(() => {
    form.setData({
      nome: classe.nome,
      ordem: classe.ordem,
    });
  }, [classe]);

  const handleSubmit = (e) => {
    e.preventDefault();

    form.patch(update.url(classe.id), {
      onSuccess: () => {
        console.log('Classe atualizada!');
      },
      onError: () => {
        console.log('Erro ao atualizar');
      },
    });
  };

  return (
    <>
      <Head title={`Editar ${classe.nome}`} />
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          value={form.data.nome}
          onChange={(e) => form.setData('nome', e.target.value)}
          disabled={form.processing}
        />
        {form.errors.nome && (
          <span className="text-red-500">{form.errors.nome}</span>
        )}

        <input
          type="number"
          value={form.data.ordem}
          onChange={(e) => form.setData('ordem', parseInt(e.target.value))}
          disabled={form.processing}
        />
        {form.errors.ordem && (
          <span className="text-red-500">{form.errors.ordem}</span>
        )}

        <button type="submit" disabled={form.processing}>
          {form.processing ? 'Atualizando...' : 'Atualizar'}
        </button>
      </form>
    </>
  );
}
```

---

## DELETE

### Opção 1: `<Link>` (Mais simples)

```jsx
// Em uma tabela ou listagem
import { Link } from '@inertiajs/react';
import { destroy } from '@/actions/App/Http/Controllers/ClasseController';

export function ClasseRow({ classe }) {
  return (
    <tr>
      <td>{classe.nome}</td>
      <td>
        <Link
          href={destroy.url(classe.id)}
          method="delete"
          as="button"
          onClick={() => {
            if (!confirm('Tem certeza?')) {
              return false;
            }
          }}
        >
          Deletar
        </Link>
      </td>
    </tr>
  );
}
```

### Opção 2: `router.delete()` (Mais controle)

```jsx
// pages/classes/index.jsx
import { router, usePage } from '@inertiajs/react';
import { destroy } from '@/actions/App/Http/Controllers/ClasseController';

export default function Index() {
  const { classes } = usePage().props;

  const handleDelete = (id) => {
    if (confirm('Tem certeza que deseja deletar?')) {
      router.delete(destroy.url(id), {
        onSuccess: () => {
          console.log('Deletado com sucesso');
        },
        onError: () => {
          alert('Erro ao deletar');
        },
      });
    }
  };

  return (
    <table>
      <tbody>
        {classes.map((classe) => (
          <tr key={classe.id}>
            <td>{classe.nome}</td>
            <td>
              <button onClick={() => handleDelete(classe.id)}>
                Deletar
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```

---

## Wayfinder URLs

### Como usar Wayfinder para gerar URLs type-safe

```jsx
// Importar ações do controller
import {
  index,
  create,
  store,
  show,
  edit,
  update,
  destroy,
} from '@/actions/App/Http/Controllers/ClasseController';

// Usar as funções
index.url()                    // /classes
create.url()                   // /classes/create
store.url()                    // /classes
store.form()                   // { action: "/classes", method: "post" }

show.url(1)                    // /classes/1
edit.url(1)                    // /classes/1/edit
update.url(1)                  // /classes/1
update.patch(1)                // PATCH /classes/1

destroy.url(1)                 // /classes/1
destroy.delete(1)              // DELETE /classes/1

// Com query params
index.url(null, { query: { page: 2 } })  // /classes?page=2
```

---

## Backend (Laravel)

### Controller Completo

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\Classe;
use Inertia\Inertia;
use Inertia\Response;

class ClasseController
{
    public function index(): Response
    {
        $classes = Classe::orderBy('ordem')->get();
        return Inertia::render('classes/index', ['classes' => $classes]);
    }

    public function create(): Response
    {
        return Inertia::render('classes/create');
    }

    public function store(StoreClasseRequest $request)
    {
        Classe::create($request->validated());
        return to_route('classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe criada com sucesso.',
        ]);
    }

    public function show(Classe $classe): Response
    {
        return Inertia::render('classes/show', ['classe' => $classe]);
    }

    public function edit(Classe $classe): Response
    {
        return Inertia::render('classes/edit', ['classe' => $classe]);
    }

    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $classe->update($request->validated());
        return to_route('classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe atualizada com sucesso.',
        ]);
    }

    public function destroy(Classe $classe)
    {
        $classe->delete();
        return to_route('classes.index')->with('toast', [
            'type' => 'success',
            'message' => 'Classe deletada com sucesso.',
        ]);
    }
}
```

### Routes

```php
// routes/web.php
Route::resource('classes', ClasseController::class);
```

### Validation

```php
<?php

namespace App\Http\Requests\Classe;

use Illuminate\Foundation\Http\FormRequest;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255|unique:classes',
            'ordem' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório',
            'nome.unique' => 'Este nome já existe',
            'ordem.required' => 'Ordem é obrigatória',
        ];
    }
}
```

```php
<?php

namespace App\Http\Requests\Classe;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255|unique:classes,nome,' . $this->classe->id,
            'ordem' => 'required|integer|min:1',
        ];
    }
}
```

---

## Comparação Rápida

| Operação | `<Form>` | `useForm()` |
|----------|----------|-----------|
| **Criação** | ✅ Simples | ✅ Mais controle |
| **Edição** | ✅ Fácil com `defaultValue` | ✅ Melhor com `useEffect` |
| **Validação** | ✅ Automática | ✅ Automática |
| **Callbacks** | ✅ Via props | ✅ Via `.post/.patch` |
| **Reset** | ✅ `resetOnSuccess` | ✅ `form.reset()` |
| **Curva Aprendizado** | ✅ Menor | ❌ Maior |

---

## Dicas de Boas Práticas

1. **Use `<Form>` para 80% dos casos** — é mais simples e menos boilerplate
2. **Use `useForm()` quando precisar de:**
   - Validação customizada antes do submit
   - Mostrar preview dos dados
   - Operações complexas
3. **Sempre use Wayfinder** — evita hardcoded URLs
4. **Sempre valide no backend** — nunca confie só no frontend
5. **Use `onSuccess` e `onError`** — para feedback ao usuário

---

## Recursos

- [Documentação Inertia React v3](https://inertiajs.com/forms)
- [Wayfinder Documentation](https://laravel.com/docs/wayfinder)
- [Laravel Form Requests](https://laravel.com/docs/validation#form-request-validation)
