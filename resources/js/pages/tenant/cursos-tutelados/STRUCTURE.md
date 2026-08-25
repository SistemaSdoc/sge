# Estrutura de Componentes - Curso Tutelado

## Visão Geral

Este documento explica a organização atual de componentes na pasta `cursos-tutelados` e como adicionar novos componentes mantendo a consistência e clareza do projeto.

## Estrutura Atual

```
cursos-tutelados/
├── STRUCTURE.md (este arquivo)
├── create.jsx                          # Página: Criar novo curso tutelado
├── edit.jsx                            # Página: Editar curso tutelado
├── show.jsx                            # Página: Visualizar detalhes do curso
│
├── components/                         # ⭐ Componentes da página raiz
│   ├── forms/
│   │   ├── create.form.jsx            # Form para criar curso
│   │   └── edit.form.jsx              # Form para editar curso
│   └── tabs/
│       ├── tab-turmas.jsx             # Aba com lista de turmas
│       └── tab-professores.jsx        # Aba com lista de professores
│
├── classes/                            # Secção: Classes (Ano/Série)
│   ├── create.jsx
│   ├── show.jsx
│   │
│   └── turnos/                         # Subsecção: Turnos
│       ├── disciplinas/                # Disciplinas
│       │   ├── create.jsx
│       │   └── components/
│       │       └── disciplina-form.jsx  # ⭐ Co-localizado
│       │
│       └── turmas/                     # Turmas
│           ├── create.jsx
│           ├── show.jsx
│           │
│           ├── components/             # ⭐ Componentes da turma
│           │   ├── turma-form.jsx
│           │   ├── tabs/
│           │   │   ├── tab-alunos.jsx
│           │   │   ├── tab-disciplinas.jsx
│           │   │   ├── tab-grupos-pap.jsx
│           │   │   └── tab-recurso.jsx
│           │   └── horarios/
│           │       ├── horarios-dialog.jsx
│           │       └── horarios-form.jsx
│           │
│           ├── disciplinas/
│           │   ├── notas/
│           │   │   ├── create.jsx
│           │   │   ├── index.jsx
│           │   │   └── components/     # ⭐ Co-localizado
│           │   │       ├── lancamentos-table.jsx
│           │   │       ├── lancamentos-recurso-table.jsx
│           │   │       └── notas-table.jsx
│           │   │
│           │   └── professores/
│           │       ├── create.jsx
│           │       └── components/     # ⭐ Co-localizado
│           │           └── professor-form.jsx
│           │
│           └── pap/                    # ✅ Padrão bem consolidado
│               ├── banca-create.jsx
│               ├── create.jsx
│               ├── data-defesa-create.jsx
│               ├── show.jsx
│               └── components/         # ⭐ Co-localizado
│                   ├── banca-form.jsx
│                   ├── data-defesa-form.jsx
│                   ├── grupo-pap-cards.jsx
│                   ├── grupo-pap-form.jsx
│                   ├── grupo-pap-table.jsx
│                   └── tabs/
│                       ├── tab-banca.jsx
│                       └── tab-integrantes.jsx
│
└── professores/                        # Secção: Professores
    ├── create.jsx
    └── components/                     # ⭐ Co-localizado
        └── professor-form.jsx
```

---

## Padrão de Organização

### Padrão Correto: Co-localização (Co-location)

**Componentes vivem na mesma pasta que as páginas que os usam:**

```jsx
// ✅ CERTO
// pages/cursos-tutelados/classes/turnos/turmas/show.jsx
import { TabAlunos } from './components/tabs/tab-alunos';
import { TabDisciplinas } from './components/tabs/tab-disciplinas';

export default function Show() {
  return <Tabs>...</Tabs>;
}

// O arquivo está em:
// pages/cursos-tutelados/classes/turnos/turmas/components/tabs/tab-alunos.jsx
```

**Benefícios:**

- 📍 Fácil encontrar componentes relacionados
- 🔗 Imports curtos e claros: `./components/tab-alunos`
- 🧹 Refatoração segura (mover pasta = mover componentes)
- 📦 Componentes auto-contidos

---

## 📝 Como Adicionar Novos Componentes

### Cenário 1: Novo formulário em uma página existente

**Caso:** Adicionar `turma-edit-form` na página de edição de turma

```
cursos-tutelados/classes/turnos/turmas/
├── edit.jsx                            # ← Página existente
├── components/
│   ├── turma-form.jsx                  # Existente
│   └── turma-edit-form.jsx             # ✅ Novo componente aqui!
```

**Arquivo:** `resources/js/pages/cursos-tutelados/classes/turnos/turmas/components/turma-edit-form.jsx`

**Uso na página:**

```jsx
import { TurmaEditForm } from './components/turma-edit-form';

export default function Edit() {
  return <TurmaEditForm />;
}
```

---

### Cenário 2: Nova aba/tab em uma página

**Caso:** Adicionar `tab-avaliacoes` na página de turma

```
cursos-tutelados/classes/turnos/turmas/
├── show.jsx                            # ← Página existente
├── components/tabs/
│   ├── tab-alunos.jsx                  # Existente
│   ├── tab-disciplinas.jsx             # Existente
│   └── tab-avaliacoes.jsx              # ✅ Nova aba aqui!
```

**Arquivo:** `resources/js/pages/cursos-tutelados/classes/turnos/turmas/components/tabs/tab-avaliacoes.jsx`

**Uso na página:**

```jsx
import { TabAlunos } from './components/tabs/tab-alunos';
import { TabAvaliacoes } from './components/tabs/tab-avaliacoes';

export default function Show() {
  return (
    <Tabs>
      <TabsList>
        <TabsTrigger value="alunos">Alunos</TabsTrigger>
        <TabsTrigger value="avaliacoes">Avaliações</TabsTrigger>
      </TabsList>
      <TabsContent value="alunos">
        <TabAlunos />
      </TabsContent>
      <TabsContent value="avaliacoes">
        <TabAvaliacoes />
      </TabsContent>
    </Tabs>
  );
}
```

---

### Cenário 3: Nova subsecção com suas próprias páginas e componentes

**Caso:** Adicionar `relatórios` dentro de turmas com `create.jsx`, `index.jsx` e componentes

```
cursos-tutelados/classes/turnos/turmas/
├── relatorios/                         # ✅ Nova subsecção
│   ├── create.jsx                      # Página: Criar relatório
│   ├── index.jsx                       # Página: Lista de relatórios
│   └── components/                     # Componentes dessa subsecção
│       ├── relatorio-form.jsx
│       ├── relatorio-table.jsx
│       └── relatorio-filters.jsx
```

**Estrutura de pastas:**

```bash
mkdir -p resources/js/pages/cursos-tutelados/classes/turnos/turmas/relatorios/components
```

---

## 🗂️ Regras Fundamentais

### 1. **Componentes Raiz** (na pasta raiz `components/`)

- ✅ **DEVEM** estar em `cursos-tutelados/components/`
- Exemplos: `create.form.jsx`, `edit.form.jsx`, `tab-turmas.jsx`, `tab-professores.jsx`
- São usados pelas páginas da raiz: `create.jsx`, `edit.jsx`, `show.jsx`

### 2. **Componentes Co-localizados** (em `components/` de cada página)

- ✅ **DEVEM** estar em `./components/` da página que os usa
- Exemplos: `tab-alunos.jsx` dentro de `turmas/components/tabs/`
- Imports: `import { TabAlunos } from './components/tabs/tab-alunos'`

### 3. **Nomeação de Arquivos**

- 📝 **PascalCase** para componentes React: `TabAlunos.jsx`, `TurmaForm.jsx`
- 📝 **kebab-case** para arquivo: `tab-alunos.jsx`, `turma-form.jsx`

### 4. **Estrutura de Pastas**

- 📁 Cada página com `create.jsx`, `show.jsx`, `edit.jsx` recebe sua pasta `components/`
- 📁 Se há muitos componentes, criar subpastas: `components/tabs/`, `components/forms/`, etc.
- 📁 Máximo 2-3 níveis de profundidade dentro de `components/`

### 5. **Quando Criar Nova Página**

Se você precisa de uma nova página (ex: `relatorios/create.jsx`), **sempre crie uma pasta `components/`** para seus componentes, mesmo que vazia no início.

---

## 🔄 Exemplo Prático: Adicionar Nova Funcionalidade

### Objetivo: Adicionar "Certificados" dentro de Turmas

**Passo 1:** Criar pastas

```bash
mkdir -p resources/js/pages/cursos-tutelados/classes/turnos/turmas/certificados/components
```

**Passo 2:** Criar páginas

```jsx
// resources/js/pages/cursos-tutelados/classes/turnos/turmas/certificados/index.jsx
import { CertificadosList } from './components/certificados-list';

export default function Index() {
  return <CertificadosList />;
}
```

```jsx
// resources/js/pages/cursos-tutelados/classes/turnos/turmas/certificados/create.jsx
import { CertificadoForm } from './components/certificado-form';

export default function Create() {
  return <CertificadoForm />;
}
```

**Passo 3:** Criar componentes

```jsx
// resources/js/pages/cursos-tutelados/classes/turnos/turmas/certificados/components/certificados-list.jsx
export function CertificadosList() {
  // ...
}
```

```jsx
// resources/js/pages/cursos-tutelados/classes/turnos/turmas/certificados/components/certificado-form.jsx
export function CertificadoForm() {
  // ...
}
```

**Resultado:**

```
turmas/
├── certificados/
│   ├── index.jsx
│   ├── create.jsx
│   └── components/
│       ├── certificados-list.jsx
│       └── certificado-form.jsx
```

---

## ❌ Padrões a Evitar

```jsx
// ❌ ERRADO: Import com path muito longo
import { TabAlunos } from '../../../components/classes/turnos/turmas/tabs/tab-alunos';

// ✅ CERTO: Import curto
import { TabAlunos } from './components/tabs/tab-alunos';
```

```jsx
// ❌ ERRADO: Componente em pasta central desorganizada
// pages/components/classes/turnos/turmas/tab-alunos.jsx

// ✅ CERTO: Componente perto da página que usa
// pages/cursos-tutelados/classes/turnos/turmas/components/tabs/tab-alunos.jsx
```

```jsx
// ❌ ERRADO: Misturar componentes de diferentes níveis
pages/cursos-tutelados/
├── components/
│   ├── classes/
│   ├── professores/
│   └── turmas/

// ✅ CERTO: Cada nível tem seus componentes
pages/cursos-tutelados/
├── components/          # Componentes da raiz
├── classes/
│   └── components/      # Componentes de classes
├── professores/
│   └── components/      # Componentes de professores
└── classes/turnos/turmas/
    └── components/      # Componentes de turmas
```

---

## 📊 Checkliste para Nova Adição

- [ ] Criei a pasta `components/` (se não existir)
- [ ] Criei o arquivo com nome em **kebab-case**: `novo-componente.jsx`
- [ ] Exportei o componente com nome em **PascalCase**: `export function NovoComponente()`
- [ ] O import na página é curto: `import { NovoComponente } from './components/novo-componente'`
- [ ] Não uso paths com `../../../components/`
- [ ] Se é uma aba, está em `components/tabs/`
- [ ] Se é um formulário, está em `components/forms/` (se houver muitos) ou diretamente em `components/`
- [ ] Segui o padrão do PAP como referência

---

## 🎓 Referência: Pasta PAP (Padrão Consolidado ✅)

A pasta `pap/` é o melhor exemplo de organização bem feita:

```
pap/
├── banca-create.jsx
├── create.jsx
├── data-defesa-create.jsx
├── show.jsx
└── components/
    ├── banca-form.jsx
    ├── data-defesa-form.jsx
    ├── grupo-pap-cards.jsx
    ├── grupo-pap-form.jsx
    ├── grupo-pap-table.jsx
    └── tabs/
        ├── tab-banca.jsx
        └── tab-integrantes.jsx
```

**Por que é bom:**

- ✅ Componentes co-localizados
- ✅ Subpastas organizadas por tipo (tabs/)
- ✅ Imports curtos na página
- ✅ Fácil encontrar tudo relacionado

---

## 💡 Dicas

1. **Antes de criar novo componente**, verifique se já existe algo similar em `components/` ou em outra página
2. **Use as mesmas convenções** que já existem no projeto (nomes, estrutura, imports)
3. **Quando em dúvida**, siga o padrão da pasta `pap/` como referência
4. **Se o componente cresce muito**, considere dividir em subcomponentes na mesma pasta
5. **Teste os imports** após criar componentes novos: `npm run build` ou `npm run dev`

---

## 📞 Suporte

Se encontrar algo desorganizado ou tiver dúvidas sobre onde colocar um novo componente, sempre:

1. Procure um padrão similar no projeto (ex: procure "tab-" se está criando uma aba)
2. Siga o padrão do PAP como referência
3. Coloque em `./components/` da página que o usa

**Última regra:** _"Se posso alcançar o componente com `./components/`, está no lugar certo!"_
