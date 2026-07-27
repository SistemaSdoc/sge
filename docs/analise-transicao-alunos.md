# Análise da lógica de transição de alunos entre turmas/classes

## Resumo executivo

A lógica atual não está a “apagar” o aluno do sistema quando ele transita. O que acontece, na prática, é o seguinte:

- é criada uma nova relação na tabela de junção entre aluno e turma;
- a relação antiga passa a estar inativa;
- as consultas da interface normalmente mostram apenas as relações ativas;
- as notas continuam ligados à relação antiga, porque elas apontam para o registo de turma-aluno e não para a turma diretamente.

Isto faz parecer que o aluno “some” da turma antiga, embora os dados ainda existam no banco. O problema é mais de visibilidade/consumo da informação do que de perda física dos dados.

---

## 1. Como o banco está estruturado

### 1.1 Tabelas principais

- [app/Models/Aluno.php](app/Models/Aluno.php): representa o aluno.
- [app/Models/Turma.php](app/Models/Turma.php): representa a turma.
- [database/migrations/2026_06_01_125720_create_inscricoes_table.php](database/migrations/2026_06_01_125720_create_inscricoes_table.php): cria a tabela pivot `turma_aluno`.
- [app/Models/TurmaAluno.php](app/Models/TurmaAluno.php): representa a relação aluno↔turma.
- [database/migrations/2026_06_01_125810_create_notas_table.php](database/migrations/2026_06_01_125810_create_notas_table.php): cria as notas ligadas a um registo de `turma_aluno`.
- [database/migrations/2026_06_25_092001_add_resultado_to_turma_aluno_table.php](database/migrations/2026_06_25_092001_add_resultado_to_turma_aluno_table.php): adiciona o campo `resultado` à tabela pivot.

### 1.2 A tabela pivot `turma_aluno`

A relação entre aluno e turma não é feita diretamente em `alunos` ou `turmas`, mas sim numa tabela pivot chamada `turma_aluno`.

Os campos mais importantes são:

- `id`: identificador do registo de matrícula/associação;
- `turma_id`: a turma onde o aluno esteve ou está;
- `aluno_id`: o aluno;
- `activo`: indica se esse registo é o atual/ativo;
- `situacao`: estado da relação (por exemplo, `activo`, `transitado`, `retido`, etc.);
- `resultado`: resultado académico associado a esse registo.
- `created_at`, `updated_at`: controlo temporal.

Este modelo é fundamental porque permite guardar histórico. Um aluno pode ter vários registos em `turma_aluno` ao longo do tempo.

### 1.3 As notas

As notas não apontam diretamente para a turma nem para o aluno. Elas apontam para um registo concreto de `turma_aluno`:

- [app/Models/Nota.php](app/Models/Nota.php)
- [database/migrations/2026_06_01_125810_create_notas_table.php](database/migrations/2026_06_01_125810_create_notas_table.php)

Ou seja:

- uma nota pertence a um `TurmaAluno` específico;
- se o aluno passar para outra turma, o registo antigo continua a existir;
- as notas do período em que esteve na turma antiga ficam associadas ao registo antigo.

---

## 2. Como as relações Eloquent estão definidas

### 2.1 No modelo de aluno

Em [app/Models/Aluno.php](app/Models/Aluno.php), o aluno tem:

- `turmas()`: relação many-to-many para todos os registos de turma;
- `turmaActual()`: relação que tenta buscar a turma atual com base em `situacao`;
- `turmaAlunoActual()`: procura o registo ativo da turma atual.

O ponto importante é que a lógica de “turma atual” depende muito do campo `activo` da pivot.

### 2.2 No modelo de turma

Em [app/Models/Turma.php](app/Models/Turma.php), a turma tem:

- `alunos()`: todos os alunos ligados à turma;
- `alunosActivos()`: só os alunos com `activo = true`.

### 2.3 No modelo de turma-aluno

Em [app/Models/TurmaAluno.php](app/Models/TurmaAluno.php), a relação tem:

- `turma()`: a turma associada;
- `aluno()`: o aluno associado;
- `notas()`: todas as notas desse aluno naquela relação concreta.

---

## 3. Como a lógica atual de transição funciona

A lógica principal está em [app/Http/Controllers/ProgressaoController.php](app/Http/Controllers/ProgressaoController.php) e em [app/Services/ConfirmacaoMatriculaService.php](app/Services/ConfirmacaoMatriculaService.php).

### 3.1 Fluxo de preview

No `preview()` do controller:

- busca os alunos “atuais” da turma atual através de `TurmaAluno` com `activo = true`;
- calcula a situação final do aluno (transita, retido, recurso, etc.);
- mostra a lista para o utilizador escolher a turma destino.

Ou seja, o preview trabalha sobre os alunos atualmente ativos naquela turma.

### 3.2 Fluxo de execução da progressão

No `store()` do controller, para cada aluno ativo da turma atual:

- calcula a decisão académica (`TRANSITAR`, `RETER`, `AGUARDAR_RECURSO`, etc.);
- se a decisão for `TRANSITAR`, chama o método `moverParaProximaClasse()`;
- se for `RETER`, cria um novo registo na mesma turma e desativa o anterior;
- se for `AGUARDAR_RECURSO`, apenas atualiza a situação do registo atual.

### 3.3 O que acontece no caso de transitar

O método `moverParaProximaClasse()` cria uma nova entrada em `turma_aluno` para a turma destino e desativa a antiga.

O fluxo atual é este:

1. cria um novo registo com:
   - `turma_id` = turma de destino;
   - `aluno_id` = mesmo aluno;
   - `activo` = `true`;
   - `situacao` = `activo`;
2. atualiza o registo antigo para:
   - `activo` = `false`;
   - `situacao` = `transitado`.

### 3.4 O que acontece no caso de não transitar

Se o aluno não transita, o fluxo atual cria um novo registo na mesma turma e desativa o registo anterior, marcando-o como `retido`.

Ou seja, mesmo quem “repete” ganha um novo registo ativo e o antigo é fechado.

---

## 4. Por que o aluno “some” da turma antiga

Este efeito vem do uso do campo `activo` como o “estado corrente” da relação.

### 4.1 Onde isso aparece na interface

Vários controladores e queries usam filtros como:

- `wherePivot('activo', true)`
- `where('activo', true)`

Exemplos:

- [app/Http/Controllers/ClasseTurnoTurmaController.php](app/Http/Controllers/ClasseTurnoTurmaController.php)
- [app/Http/Controllers/InstituicaoCurso/TurmaController.php](app/Http/Controllers/InstituicaoCurso/TurmaController.php)
- [app/Http/Controllers/AlunoController.php](app/Http/Controllers/AlunoController.php)
- [app/Models/Turma.php](app/Models/Turma.php)

Ou seja, quando o registo antigo é marcado como inativo, a interface deixa de mostrar esse aluno na turma antiga.

### 4.2 O que isto significa na prática

O aluno não desaparece do banco. Ele desaparece da visão “atual” da turma antiga porque o sistema está a considerar apenas as relações ativas.

---

## 5. O que acontece às notas e ao histórico

Este é o ponto mais importante para o problema que você descreveu.

### 5.1 As notas ficam ligadas ao registo antigo

As notas são guardadas em [app/Models/Nota.php](app/Models/Nota.php) com referência a `turma_aluno_id`.

Quando um aluno transita:

- o registo antigo de `turma_aluno` continua a existir;
- as notas do período em que esteve na turma antiga continuam ligadas a esse registo;
- o novo registo da nova turma é outra entidade distinta.

### 5.2 Portanto, as notas não são perdidas

As notas não desaparecem do banco. Elas ficam “presas” ao registo de turma antigo, que passou a estar inativo.

O problema é que a interface atual normalmente não está a exibir esse histórico de forma explícita, porque a lógica de consulta está focada em registos ativos.

---

## 6. O que está a acontecer com a informação do aluno

### 6.1 O aluno fica com dois registos de matrícula ao longo do tempo

Depois de transitar, normalmente o sistema termina com:

- um registo antigo, desativado, na turma antiga;
- um registo novo, ativo, na nova turma.

### 6.2 O “estado corrente” é o novo registo

O novo registo é o que a aplicação usa para mostrar a turma atual do aluno, porque o antigo passou a estar inativo.

### 6.3 O histórico fica escondido

O histórico da turma antiga e as notas que ele tirou nessa turma ficam disponíveis no banco, mas ficam menos visíveis porque a aplicação atual não faz uma navegação explícita por “todos os registos de turma do aluno”.

---

## 7. Inconsistências importantes no código atual

Há alguns pontos que reforçam este comportamento e que merecem atenção:

### 7.1 O campo `situacao` da pivot não está alinhado com o schema original

O migration original da tabela `turma_aluno` define um enum limitado. Mas o código passa a escrever valores como:

- `transitado`
- `retido`
- `aguardando_recurso`
- `reprovado_recurso`

Isto é uma inconsistência importante entre schema e lógica aplicada.

### 7.2 O método `moverParaProximaClasse()` atualiza duas vezes o mesmo registo

No [app/Http/Controllers/ProgressaoController.php](app/Http/Controllers/ProgressaoController.php), o método faz duas chamadas seguidas para `update()` no mesmo objeto `$ta`.

Isso não é o principal problema visual, mas é um sinal de que a implementação pode estar a ser feita de forma simplificada e pouco robusta.

### 7.3 O sistema usa “ativo/inativo” como proxy de “presente/ausente”

Isso é o que provoca o efeito de o aluno “sumir” da turma antiga da perspetiva da UI. O design não está a diferenciar claramente entre:

- “turma atual”;
- “turma anterior/histórico”;
- “turma futura”;
- “matrícula fechada”.

---

## 8. Conclusão

A lógica atual funciona como uma transição de “matrícula ativa” para uma nova matrícula ativa:

- cria uma nova relação em `turma_aluno` para a nova turma;
- marca a relação antiga como não ativa;
- faz com que a aplicação deixe de mostrar o aluno na turma antiga;
- mantém as notas e o histórico ligado à relação antiga, mas escondido pela lógica atual de consulta.

Em termos práticos, o seu problema não é que os dados foram perdidos do banco. O problema é que a aplicação está a tratar a relação antiga como “não atual” e, por isso, a interface deixa de a mostrar como parte da turma antiga.

Se quiser, o próximo passo natural é alterar esta lógica para:

1. manter explicitamente o histórico das turmas do aluno;
2. mostrar tanto a turma atual como as turmas anteriores;
3. garantir que as notas sejam exibidas a partir do registo histórico correto.
