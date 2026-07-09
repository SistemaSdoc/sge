# Análise de permissões para disciplinas numa turma

## Resumo curto

A lógica que procuras faz sentido, mas a proteção precisa estar em duas camadas:

1. Proteger o acesso à própria turma.
2. Proteger o acesso a cada disciplina daquela turma.

O ponto mais importante é que a tela de turma não deve entregar ao frontend uma lista de disciplinas que o utilizador não pode abrir. A lista deve ser filtrada no servidor e, mesmo assim, as rotas de disciplina devem continuar a ser protegidas por policy/controller.

---

## O que descobri no projeto

### 1) A relação real entre turma, disciplina e professor

A ligação que importa aqui é esta:

- Turma -> CursoClasseTurno -> ClasseTurnoDisciplina
- ClasseTurnoDisciplina -> TurmaDisciplinaProfessor -> Professor
- Professor -> User

Os modelos relevantes são:

- [app/Models/Turma.php](app/Models/Turma.php)
- [app/Models/TurmaDisciplinaProfessor.php](app/Models/TurmaDisciplinaProfessor.php)
- [app/Models/ClasseTurnoDisciplina.php](app/Models/ClasseTurnoDisciplina.php)
- [app/Models/Professor.php](app/Models/Professor.php)

Ou seja, a permissão para ver uma disciplina numa turma não é “qualquer disciplina da turma”, mas sim “a disciplina daquela turma onde existe um registo de associação professor/turma/disciplina”.

### 2) Já existe uma política para turmas

A política [app/Policies/TurmaPolicy.php](app/Policies/TurmaPolicy.php) já faz uma parte do trabalho:

- verifica se o utilizador tem a permissão de ver turmas;
- verifica se a turma pertence à instituição do utilizador;
- para professores, restringe o acesso a turmas onde o professor leciona.

Isso está bem alinhado com o teu pedido.

### 3) O ponto fraco atual está na listagem de disciplinas

O controlador da tela de turma, [app/Http/Controllers/ClasseTurnoTurmaController.php](app/Http/Controllers/ClasseTurnoTurmaController.php), faz o carregamento das disciplinas da turma para a view de show.

Nesse fluxo, a lista é construída a partir das disciplinas da turma sem aplicar, ainda, uma filtragem baseada no professor atual.

O frontend, em [resources/js/pages/cursos-tutelados/classes/turnos/turmas/components/tabs/tab-disciplinas.jsx](resources/js/pages/cursos-tutelados/classes/turnos/turmas/components/tabs/tab-disciplinas.jsx), transforma cada linha numa ação clicável para abrir as notas da disciplina.

Ou seja, o problema não está só no frontend. O servidor está a entregar mais do que o utilizador deveria ver.

### 4) Existe um modelo de associação ideal para a regra

Já existe a policy [app/Policies/TurmaDisciplinaProfessorPolicy.php](app/Policies/TurmaDisciplinaProfessorPolicy.php), mas está vazia e devolve false.

Este é o local mais natural para a regra de “este professor pode ver esta disciplina nesta turma”.

---

## O que eu considero o caminho mais consistente

### A) Manter a regra de acesso à turma no nível da turma

A política de turma continua a ser o primeiro gate.

Para professores, a regra atual já é a certa:

- só pode ver turmas onde leciona;
- não pode abrir turmas em que não leciona.

Isto evita que um professor consiga aceder a uma turma inteira por URL direta.

### B) Filtrar as disciplinas da turma no servidor

Na resposta da tela de show da turma, a lista de disciplinas deve ser filtrada de forma dinâmica:

- SuperAdmin / Director / Subdirector / Secretaria: veem todas as disciplinas da turma.
- Professor: vê apenas as disciplinas em que está associado a essa turma através de um registo em TurmaDisciplinaProfessor.

Este filtro deve acontecer no controlador, antes de a resposta chegar ao frontend.

### C) Proteger também as rotas “filha” das disciplinas

Mesmo com a lista filtrada, ainda é preciso proteger o acesso direto às rotas de notas/disciplina.

Os controladores relevantes são:

- [app/Http/Controllers/NotaDisciplinaController.php](app/Http/Controllers/NotaDisciplinaController.php)
- [app/Http/Controllers/NotaDisciplinaRecursoController.php](app/Http/Controllers/NotaDisciplinaRecursoController.php)

Nesses controladores, a ideia seria resolver o registo de TurmaDisciplinaProfessor para a combinação:

- turma
- disciplina
- professor atual

e só então permitir o acesso.

Isso dá a proteção mais forte, porque um utilizador não consegue contornar a UI apenas digitando a URL.

---

## Como eu sugiro implementar a lógica

### Opção recomendada

#### 1. Completar a policy de TurmaDisciplinaProfessor

A policy [app/Policies/TurmaDisciplinaProfessorPolicy.php](app/Policies/TurmaDisciplinaProfessorPolicy.php) é o ponto certo para isso.

A regra seria algo como:

- se o utilizador não tiver permissão para ver a turma, negar;
- se for professor, só permitir se o registo estiver associado ao seu professor_id;
- se for Director/Subdirector/Secretaria, permitir apenas se a turma pertencer à sua instituição;
- SuperAdmin continua a ter acesso.

#### 2. Filtrar a listagem da turma no controlador

No controlador [app/Http/Controllers/ClasseTurnoTurmaController.php](app/Http/Controllers/ClasseTurnoTurmaController.php), a query das disciplinas deve ser ajustada para o utilizador atual.

O comportamento seria:

- role professor: só disciplinas com registo `turma_disciplina_professor` onde `professor_id` = professor do utilizador e `turma_id` = turma atual;
- outros roles: todas as disciplinas da turma.

#### 3. Guardar a mesma lógica no acesso às rotas de notas

Nos controladores de notas, antes de buscar alunos/notas, seria ideal fazer:

- localizar o registo `TurmaDisciplinaProfessor` para a turma e disciplina;
- validar contra a policy;
- se não tiver acesso, abortar/retornar 403.

Isso garante segurança real.

---

## Precedente já existente no projeto

Há um ponto do projeto que já segue um raciocínio semelhante em outra parte:

- [app/Http/Resources/TurmaResource.php](app/Http/Resources/TurmaResource.php)

Esse recurso já faz filtragem de disciplinas para professor. Portanto, a lógica não é nova no projeto; só está a ser aplicada noutro contexto.

A diferença é que a tela de show da turma atual não está a usar esse filtro e, por isso, a lista está a “vazar” mais do que deveria.

---

## Ponto importante de UX

Eu sugiro duas coisas em conjunto:

1. No servidor, não devolver as disciplinas para as quais o utilizador não tem acesso.
2. No frontend, não deixar a linha da disciplina como ação clicável se não houver acesso.

A segunda parte é UX, a primeira é segurança. As duas podem coexistir.

---

## Dúvidas/decisões que eu faria antes de implementar

### 1. Mostrar ou esconder a disciplina?

Eu prefiro a opção “esconder da lista” para o professor, porque a tela fica mais limpa e não gera confusão.

Se alguém tentar acessar por URL, a regra do policy deve bloquear.

### 2. O professor pode ver todas as disciplinas da turma ou só as que ele leciona?

Pelo que descreveste, a regra correta é:

- só as disciplinas em que ele está associado para essa turma.

Ou seja, mesmo que a turma exista e ele seja professor de outra disciplina na mesma turma, ele não deve ver as outras.

### 3. Deve existir uma regra específica para notas/recurso também?

Sim. A mesma regra deve valer para:

- abrir a página de notas;
- abrir a página de recurso;
- exportar mini-pauta de uma disciplina;
- qualquer rota que dependa do vínculo turma/disciplina/professor.

---

## Proposta final de implementação (sem codificar ainda)

### Fase 1

- completar [app/Policies/TurmaDisciplinaProfessorPolicy.php](app/Policies/TurmaDisciplinaProfessorPolicy.php)

### Fase 2

- aplicar filtragem das disciplinas no controlador de show da turma, em [app/Http/Controllers/ClasseTurnoTurmaController.php](app/Http/Controllers/ClasseTurnoTurmaController.php)

### Fase 3

- proteger as rotas de disciplina/notas em [app/Http/Controllers/NotaDisciplinaController.php](app/Http/Controllers/NotaDisciplinaController.php) e [app/Http/Controllers/NotaDisciplinaRecursoController.php](app/Http/Controllers/NotaDisciplinaRecursoController.php)

### Fase 4

- opcionalmente, ajustar o frontend para não deixar a disciplina clicável se não houver acesso

---

## Conclusão

A abordagem mais correta é esta:

- a turma já tem uma proteção base via [app/Policies/TurmaPolicy.php](app/Policies/TurmaPolicy.php);
- a disciplina precisa de uma proteção complementar via [app/Policies/TurmaDisciplinaProfessorPolicy.php](app/Policies/TurmaDisciplinaProfessorPolicy.php);
- a lista de disciplinas da tela de turma deve ser filtrada no servidor;
- as rotas de disciplina devem continuar a ser protegidas para não dependerem só da UI.

Se quiseres, no próximo passo eu posso transformar esta proposta em implementação real, mas sem mexer no código até confirmares esta direção.
