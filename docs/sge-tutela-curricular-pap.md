# SGE: Tutela Curricular e Grupos PAP

> Inventário da implementação existente no repositório em 2026-08-28. Os links apontam para o código real no workspace. A BD central guarda a relação de tutela; os dados académicos e PAP continuam nas bases tenant.

## 0. Modelo mental do sistema

```text
BD central
  curso_tutelado_shared
  └── IMCL (tenant tutor) -> CUA (tenant tutelado) -> curso_tutelado local

BD do CUA
  curso_tutelado
  curso_classe
  curso_classe_turno
  turmas
  alunos
  grupo_pap
  elementos_grupo_pap
  banca_juri_pap
  historico_aprovacao_pap

BD do IMCL
  utilizador autenticado, permissões e professores da tutora
```

### Regra de negócio

- Um `instituto` tem tutela própria e independente.
- Um `colegio` pode ter tutela própria ou tutela externa.
- Apenas um `instituto` pode ser tutor externo.
- O curso, turmas, alunos, notas e PAP ficam no tenant que oferece o curso, normalmente o colégio.
- O instituto tutor pode localizar e ler cursos/pautas/PAP do colégio através do vínculo central.
- O sentido inverso não é permitido: o colégio não ganha acesso às pautas/PAP da tutora.
- A troca para o tenant remoto é feita com `Tenant::run(...)`, depois de validar o vínculo central.

---

# 1. Migrations da BD central

## 1.1 `curso_tutelado_shared`

Fonte completa: [2026_08_27_155654_create_curso_tutelado_shared_table.php](database/migrations/2026_08_27_155654_create_curso_tutelado_shared_table.php)

```php
Schema::create('curso_tutelado_shared', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->string('tenant_tutor_id');
    $table->string('tenant_tutelado_id');
    $table->uuid('curso_tutelado_tutelado_id');
    $table->string('curso_nome');
    $table->unsignedTinyInteger('duracao_anos');
    $table->string('status')->default('activo');
    $table->timestamps();

    $table->index('tenant_tutor_id');
    $table->index('tenant_tutelado_id');
    $table->unique([
        'tenant_tutor_id',
        'tenant_tutelado_id',
        'curso_tutelado_tutelado_id',
    ], 'curso_tutelado_shared_unique');
});
```

### Colunas e constraints

| Coluna | Tipo | Regra |
|---|---|---|
| `id` | UUID | Chave primária |
| `tenant_tutor_id` | string | ID central do tenant instituto tutor |
| `tenant_tutelado_id` | string | ID central do tenant colégio |
| `curso_tutelado_tutelado_id` | UUID | ID do curso tutelado na BD do colégio |
| `curso_nome` | string | Snapshot do nome do curso |
| `duracao_anos` | unsigned tiny integer | Duração snapshot |
| `status` | string | Default `activo`; também usado `encerrado` |
| `created_at`, `updated_at` | timestamps | Auditoria técnica |

Existe índice para cada tenant e uma constraint única para impedir duplicar o mesmo curso no mesmo par tutor/tutelado.

## 1.2 Nome da instituição tutora

Fonte: [2026_08_27_174546_add_tutor_name_to_curso_tutelado_shared_table.php](database/migrations/2026_08_27_174546_add_tutor_name_to_curso_tutelado_shared_table.php)

```php
$table->string('tenant_tutor_nome')->nullable()->after('tenant_tutor_id');
```

É apenas um snapshot de apresentação. A identidade real continua sendo `tenant_tutor_id`.

## 1.3 Não existe tabela central de PAP

Não foi criada uma tabela central para `grupo_pap`, alunos, notas, banca ou histórico. Esses dados continuam isolados no tenant do colégio. A tabela central só localiza e autoriza o acesso ao curso.

---

# 2. Migrations da BD tenant

## 2.1 Estrutura curricular base

A estrutura local de cursos/classes/turnos está principalmente em [0002_06_01_122213_create_instituicoes_table.php](database/migrations/tenant/0002_06_01_122213_create_instituicoes_table.php) e nas migrations de turmas/professores:

- [0002_06_01_122213_create_instituicoes_table.php](database/migrations/tenant/0002_06_01_122213_create_instituicoes_table.php): `instituicoes`, `instituicao_curso`, `curso_tutelado`, `curso_classe`, `curso_classe_turno`.
- [2026_06_01_125641_create_professores_alunos_table.php](database/migrations/tenant/2026_06_01_125641_create_professores_alunos_table.php): `professores`, `alunos`, `turmas` e relações associadas.
- [2026_06_01_125831_curso_tutelado_professor_table.php](database/migrations/tenant/2026_06_01_125831_curso_tutelado_professor_table.php): professores ligados a cursos tutelados.

A relação professor/curso é:

```php
curso_tutelado_professor
- id UUID primary key
- curso_tutelado_id UUID -> curso_tutelado.id cascade
- professor_id UUID -> professores.id cascade
- tipo enum('principal', 'colaborador')
- coordenador boolean default false
- unique(curso_tutelado_id, professor_id)
- timestamps
```

## 2.2 Ligação local ao vínculo central

Fonte: [2026_08_27_155654_add_shared_tutela_fields_to_curso_tutelado_table.php](database/migrations/tenant/2026_08_27_155654_add_shared_tutela_fields_to_curso_tutelado_table.php)

```php
$table->uuid('instituicao_tutora_id')->nullable()->change();
$table->string('tipo_tutela')->default('propria');
$table->uuid('curso_tutelado_shared_id')->nullable();
$table->index('curso_tutelado_shared_id');
```

| Campo | Significado |
|---|---|
| `instituicao_tutora_id` | ID local quando a tutela é própria; fica `null` quando é externa |
| `tipo_tutela` | `propria` ou `externa` |
| `curso_tutelado_shared_id` | ID UUID da linha correspondente na BD central |

Não existe FK entre bases. O ID central é validado pela aplicação.

## 2.3 Tabelas PAP locais

Fonte: [2026_06_01_125753_create_pap_table.php](database/migrations/tenant/2026_06_01_125753_create_pap_table.php)

### `grupo_pap`

```text
id UUID primary key
 turma_id UUID -> turmas.id
 professor_tutor_id UUID -> professores.id, nullable após migration externa
 professor_tutor_externo_id UUID nullable
 professor_tutor_externo_tenant_id string nullable
 nome_grupo string
 tema_grupo string nullable
 problema text nullable
 objectivos text nullable
 status_aprovacao enum(rascunho, submetido, pendente, aprovado, reprovado, melhoria-solicitada)
 aprovado_por_id UUID -> users.id, nullable
 aprovado_por_externo_id UUID nullable
 aprovado_por_externo_tenant_id string nullable
 aprovado_por_nome string nullable
 data_aprovacao timestamp nullable
 comentario_aprovacao text nullable
 estudo_caso text nullable
 trabalho_grupo string nullable
 status enum(pendente, em-andamento, concluido)
 nota_final decimal(5,2) nullable
 data_defesa datetime nullable
 local_defesa string nullable
 created_at, updated_at timestamps
 index(status_aprovacao)
```

### `elementos_grupo_pap`

```text
id UUID primary key
grupo_pap_id UUID -> grupo_pap.id
aluno_id UUID -> alunos.id
nota_individual decimal(5,2) nullable
created_at, updated_at timestamps
```

### `banca_juri_pap`

```text
id UUID primary key
professor_id UUID -> professores.id, nullable após migration externa
professor_externo_id UUID nullable
professor_externo_tenant_id string nullable
grupo_pap_id UUID -> grupo_pap.id
funcao string
created_at, updated_at timestamps
```

## 2.4 Histórico PAP

Fonte: [2026_07_24_153756_create_historico_aprovacao_pap_table.php](database/migrations/tenant/2026_07_24_153756_create_historico_aprovacao_pap_table.php)

Base original:

```text
id UUID primary key
grupo_pap_id UUID -> grupo_pap.id cascade
utilizador_id UUID -> users.id
 tema string
problema text nullable
objectivos text nullable
estado_anterior string nullable
estado_novo string
comentario text nullable
created_at, updated_at timestamps
```

Campos externos acrescentados por [2026_08_28_082312_add_external_actor_fields_to_pap_tables.php](database/migrations/tenant/2026_08_28_082312_add_external_actor_fields_to_pap_tables.php):

```text
utilizador_id UUID nullable -> users.id nullOnDelete
utilizador_externo_id UUID nullable
utilizador_externo_tenant_id string nullable
utilizador_nome string nullable
```

A mesma migration tornou opcionais os IDs locais de actor nas três tabelas PAP e preservou as FKs locais quando o actor é local.

---

# 3. Models Eloquent

## 3.1 Central

### CursoTuteladoShared

Fonte: [CursoTuteladoShared.php](app/Models/Central/CursoTuteladoShared.php)

- Tabela: `curso_tutelado_shared`.
- Connection: central, através do trait `Stancl\\Tenancy\\Database\\Concerns\\CentralConnection`.
- Usa `HasUuids`.
- Fillable: `tenant_tutor_id`, `tenant_tutelado_id`, `curso_tutelado_tutelado_id`, `tenant_tutor_nome`, `curso_nome`, `duracao_anos`, `status`.
- Não tem relações Eloquent cross-database; os IDs de tenant/curso são referências aplicacionais.

## 3.2 Curso e currículo tenant

Fontes:

- [CursoTutelado.php](app/Models/Tenant/CursoTutelado.php)
- [Instituicao.php](app/Models/Tenant/Instituicao.php)
- [InstituicaoCurso.php](app/Models/Tenant/InstituicaoCurso.php)
- [CursoClasse.php](app/Models/Tenant/CursoClasse.php)
- [CursoClasseTurno.php](app/Models/Tenant/CursoClasseTurno.php)
- [CursoTuteladoProfessor.php](app/Models/Tenant/CursoTuteladoProfessor.php)
- [Turma.php](app/Models/Tenant/Turma.php)
- [Professor.php](app/Models/Tenant/Professor.php)
- [Aluno.php](app/Models/Tenant/Aluno.php)

Todos usam a conexão tenant dinâmica por omissão. Principais relações:

```text
Instituicao
  hasMany InstituicaoCurso
  hasMany CursoTutelado

InstituicaoCurso
  belongsTo Instituicao
  belongsTo Curso
  hasOne CursoTutelado

CursoTutelado
  belongsTo InstituicaoCurso
  belongsTo Instituicao como instituicaoTutora
  belongsTo CursoTuteladoShared por curso_tutelado_shared_id
  hasMany CursoClasse
  belongsToMany Professor via curso_tutelado_professor

CursoClasse
  belongsTo CursoTutelado
  belongsTo Classe
  hasMany CursoClasseTurno

CursoClasseTurno
  belongsTo CursoClasse
  belongsTo Turno
  hasMany Turma
  hasMany ClasseTurnoDisciplina

Turma
  belongsTo CursoClasseTurno
  belongsToMany Aluno via turma_aluno
  belongsToMany Professor via turma_disciplina_professor
  hasMany GrupoPap
  hasMany TurmaDisciplinaProfessor

Professor
  belongsTo User
  belongsToMany CursoTutelado
  hasMany GrupoPap como professor tutor
  hasMany BancaJuriPap

Aluno
  belongsTo User
  belongsToMany Turma
  belongsToMany GrupoPap via elementos_grupo_pap
```

## 3.3 Models PAP

### GrupoPap

Fonte: [GrupoPap.php](app/Models/Tenant/GrupoPap.php)

- Connection: tenant.
- Relações: `belongsTo Professor`, `belongsTo Turma`, `hasMany BancaJuriPap`, `hasMany ElementoGrupoPap`, `belongsToMany Aluno`, `hasMany HistoricoAprovacaoPap`, `belongsTo User` como `aprovadoPor`.
- Métodos de estado: `podeSerReenviado`, `podeSerEditado`, `podeDefinirTema`, `podeSermitidoAoTutor`, `podeSerAprovadoPeloTutor`, `podeSerAprovado`.
- Scopes: `pendentes`, `aprovados`, `reprovados`, `melhoriaSolicitada`.
- `instituicao()` e `instituicaoTutora()` percorrem a hierarquia da turma com null-safe.

### ElementoGrupoPap

Fonte: [ElementoGrupoPap.php](app/Models/Tenant/ElementoGrupoPap.php)

- Connection: tenant.
- `belongsTo GrupoPap`.
- `belongsTo Aluno`.
- Fillable: grupo, aluno e nota individual.

### BancaJuriPap

Fonte: [BancaJuriPap.php](app/Models/Tenant/BancaJuriPap.php)

- Connection: tenant.
- `belongsTo Professor` local quando `professor_id` está preenchido.
- `belongsTo GrupoPap`.
- Pode guardar actor externo em `professor_externo_id` + `professor_externo_tenant_id`.

### HistoricoAprovacaoPap

Fonte: [HistoricoAprovacaoPap.php](app/Models/Tenant/HistoricoAprovacaoPap.php)

- Connection: tenant.
- `belongsTo GrupoPap`.
- `belongsTo User` local quando `utilizador_id` existe.
- Actor externo: `utilizador_externo_id`, `utilizador_externo_tenant_id`, `utilizador_nome`.

---

# 4. Controllers

## 4.1 Cursos tutelados e currículo

### Tenant próprio

- [CursoTuteladoController.php](app/Http/Controllers/Tenant/CursoTuteladoController.php): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `uploadCriteriosPap`.
- [CursoTuteladoProfessorController.php](app/Http/Controllers/Tenant/CursoTuteladoProfessorController.php): gestão de professores e coordenadores.
- [InstituicaoCursoController.php](app/Http/Controllers/Tenant/InstituicaoCursoController.php): associação local entre instituição e curso.
- [CursoClasseController.php](app/Http/Controllers/Tenant/CursoClasseController.php): classes do curso.
- [CursoClasseTurnoController.php](app/Http/Controllers/Tenant/CursoClasseTurnoController.php): turnos.
- [ClasseTurnoDisciplinaController.php](app/Http/Controllers/Tenant/ClasseTurnoDisciplinaController.php): disciplinas.
- [TurmaController.php](app/Http/Controllers/Tenant/TurmaController.php): turmas.

### Instituto a consultar cursos do colégio

- [Colegios/ColegioController.php](app/Http/Controllers/Tenant/Colegios/ColegioController.php): lista colégios associados ao tutor através de `curso_tutelado_shared`.
- [Colegios/CursoTuteladoController.php](app/Http/Controllers/Tenant/Colegios/CursoTuteladoController.php): resolve vínculo central e carrega o curso no tenant colégio.
- [Colegios/CursoClasseController.php](app/Http/Controllers/Tenant/Colegios/CursoClasseController.php): resolve curso/classe no tenant tutelado e pagina turmas.
- [Colegios/ClasseTurnoTurmaController.php](app/Http/Controllers/Tenant/Colegios/ClasseTurnoTurmaController.php): resolve curso, classe, turno e turma com `whereKey` e relação pai.

### Pautas cross-tenant

- [PautaController.php](app/Http/Controllers/Tenant/PautaController.php): `indexCursos`, `indexTurmas`, `pauta`.
- O instituto lista cursos publicados na central, entra no tenant do colégio e lê turmas/pauta local.
- O colégio só resolve os seus próprios cursos.
- [ExportarPautaController.php](app/Http/Controllers/Tenant/ExportarPautaController.php): exportação CSV trimestral/final existente.
- [ExportarMiniPautaController.php](app/Http/Controllers/Tenant/ExportarMiniPautaController.php): mini-pauta existente.

## 4.2 Controllers PAP tenant próprio

Fontes e métodos principais:

- [GrupoPapController.php](app/Http/Controllers/Tenant/GrupoPapController.php): `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `definirData`.
- [GrupoPapTemaController.php](app/Http/Controllers/Tenant/GrupoPapTemaController.php): criação/edição de tema.
- [ElementoGrupoPapController.php](app/Http/Controllers/Tenant/ElementoGrupoPapController.php): criação, remoção e actualização de notas individuais.
- [BancaJuriPapController.php](app/Http/Controllers/Tenant/BancaJuriPapController.php): `create`, `store`, `edit`, `update`, `destroy` da banca.
- [GrupoPapAprovacaoController.php](app/Http/Controllers/Tenant/GrupoPapAprovacaoController.php): `pendentes`, `aprovar`, `reprovar`, `solicitarMelhoria`, `aprovarTutor`, `solicitarMelhoriaComoTutor`, `atualizar`, `reenviar`, `melhorias`, `editar`, `historico`.
- [FolhaAprovacaoController.php](app/Http/Controllers/Tenant/FolhaAprovacaoController.php): `index`, `folhaAprovacao` e conversão da nota por extenso.
- [FinalistaController.php](app/Http/Controllers/Tenant/FinalistaController.php): `papConcluido`, conclusão, reprovação e desistência de finalistas.

## 4.3 Controllers PAP de colégios

Fontes:

- [Colegios/GrupoPapController.php](app/Http/Controllers/Tenant/Colegios/GrupoPapController.php): `show` e `definirData` resolvem toda a hierarquia no tenant tutelado; `store` cria grupo local.
- [Colegios/GrupoPapAprovacaoController.php](app/Http/Controllers/Tenant/Colegios/GrupoPapAprovacaoController.php): operações externas de aprovação, reprovação e melhoria através de `withExternalGrupo`.
- [Colegios/BancaJuriPapController.php](app/Http/Controllers/Tenant/Colegios/BancaJuriPapController.php): endpoints de banca para o contexto de colégio.
- [Colegios/ElementoGrupoPapController.php](app/Http/Controllers/Tenant/Colegios/ElementoGrupoPapController.php): actualização de nota individual.
- [Colegios/NotaDisciplinaController.php](app/Http/Controllers/Tenant/Colegios/NotaDisciplinaController.php): leitura de notas/disciplinas do colégio.

### Operação remota de aprovação

O controller externo:

1. captura o tenant tutor antes do `Tenant::run`;
2. valida a permissão no tenant tutor;
3. valida `curso_tutelado_shared` na BD central;
4. entra na BD do colégio;
5. resolve curso, classe, turno, turma e grupo por IDs encadeados;
6. chama `AprovacaoTemaService` com o utilizador tutor original;
7. grava `*_externo_id`, `*_externo_tenant_id` e snapshot do nome.

---

# 5. Services e lógica de negócio

## CursoTuteladoSharedService

Fonte: [CursoTuteladoSharedService.php](app/Services/Tenant/CursoTuteladoSharedService.php)

Responsabilidades:

- `validarTutelaExterna`: só permite colégio como tutelado e instituto como tutor; rejeita auto-tutela e tenants indisponíveis.
- `executarNoTenantTutelado`: procura vínculo central activo e executa callback no tenant do colégio.
- `publicar`: cria/actualiza o registo central de tutela de forma idempotente.
- `publicarEAssociar`: publica e associa `tipo_tutela=externa` no curso local.
- `remover`: remove o vínculo central.
- `tornarPropria`: encerra o vínculo e grava tutela própria.
- `encerrar`: marca o vínculo central como `encerrado`.

## AprovacaoTemaService

Fonte: [AprovacaoTemaService.php](app/Services/Tenant/AprovacaoTemaService.php)

- `temasPendentesParaCoordenador`: localiza PAPs pendentes de cursos coordenados.
- `aprovar`, `reprovar`, `solicitarMelhoria`: alteram estado.
- `alterarEstado`: transacciona alteração do grupo e histórico.
- `reenviar`: passa melhoria/reprovação para `submetido` e cria histórico.
- Aceita `actorTenantId`; quando diferente do tenant onde o grupo está guardado, usa as colunas externas em vez das FKs locais.

## Outros services relacionados

- [PautaService.php](app/Services/Tenant/Pauta/PautaService.php): gera pautas trimestrais, finais e recurso.
- [PautaFinalGenerator.php](app/Services/Tenant/Pauta/Generators/PautaFinalGenerator.php).
- [PautaTrimestralGenerator.php](app/Services/Tenant/Pauta/Generators/PautaTrimestralGenerator.php).
- [PautaRecursoGenerator.php](app/Services/Tenant/Pauta/Generators/PautaRecursoGenerator.php).
- [CarregaDisciplinas.php](app/Services/Tenant/Pauta/Concerns/CarregaDisciplinas.php).
- [ResolveSituacaoNota.php](app/Services/Tenant/Pauta/Concerns/ResolveSituacaoNota.php).
- [DashboardProfessorService.php](app/Services/Tenant/Dashboards/DashboardProfessorService.php).

---

# 6. Form Requests e validação

## Tutela curricular

- [StoreCursoTuteladoRequest.php](app/Http/Requests/Tenant/StoreCursoTuteladoRequest.php): valida curso, duração, classes, nível e `tenant_tutor_id`.
- [TutelaRequest.php](app/Http/Requests/Tenant/TutelaRequest.php): request de tutela existente.
- [InstituicaoCurso/StoreProfessorRequest.php](app/Http/Requests/Tenant/InstituicaoCurso/StoreProfessorRequest.php).
- [InstituicaoCurso/UpdateProfessorTurnosRequest.php](app/Http/Requests/Tenant/InstituicaoCurso/UpdateProfessorTurnosRequest.php).
- [Professor/StoreProfessoresRequest.php](app/Http/Requests/Tenant/Professor/StoreProfessoresRequest.php).
- [Professor/UpdateProfessoresRequest.php](app/Http/Requests/Tenant/Professor/UpdateProfessoresRequest.php).

## PAP

- [GrupoPap/StoreRequest.php](app/Http/Requests/Tenant/GrupoPap/StoreRequest.php): tutor titular, nome, tema, alunos, 13ª classe e regra `ProfessorTitularDoCurso`.
- [GrupoPap/UpdateRequest.php](app/Http/Requests/Tenant/GrupoPap/UpdateRequest.php): nome, tema, estudo de caso, estado, nota, defesa, professor e alunos.
- [GrupoPap/DefinirDataDefesaRequest.php](app/Http/Requests/Tenant/GrupoPap/DefinirDataDefesaRequest.php): data `Y-m-d`, hora `H:i` e local.
- [BancaJuriPap/StoreRequest.php](app/Http/Requests/Tenant/BancaJuriPap/StoreRequest.php).
- [BancaJuriPap/UpdateRequest.php](app/Http/Requests/Tenant/BancaJuriPap/UpdateRequest.php).
- [BancaPapRequest.php](app/Http/Requests/Tenant/BancaPapRequest.php): request legado.
- [ElementosGrupoPap/StoreRequest.php](app/Http/Requests/Tenant/ElementosGrupoPap/StoreRequest.php).
- [ElementosGrupoPap/ActualizarNotaRequest.php](app/Http/Requests/Tenant/ElementosGrupoPap/ActualizarNotaRequest.php).

## Rules

- [ProfessorTitularDoCurso.php](app/Rules/ProfessorTitularDoCurso.php).
- [ProfessorNaoNaBanca.php](app/Rules/ProfessorNaoNaBanca.php).
- [AlunoNaoPertencenteAoGrupo.php](app/Rules/AlunoNaoPertencenteAoGrupo.php).

---

# 7. Routes

## Rotas tenant próprias

Fonte principal: [routes/tenant.php](routes/tenant.php)

Inclui:

- cursos tutelados e professores;
- classes, turnos, disciplinas e turmas;
- `pap` e recursos aninhados;
- tema PAP;
- elementos e notas individuais;
- banca;
- folha de aprovação;
- aprovação, reprovação, melhoria, reenvio, data de defesa e histórico.

Nomes relevantes:

```text
tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show
turma.pap.definir-data
turma.pap.folha-aprovacao
grupo-pap-aprovacao.aprovar
grupo-pap-aprovacao.reprovar
grupo-pap-aprovacao.solicitar-melhoria
grupo-pap-aprovacao.aprovar-tutor
grupo-pap-aprovacao.solicitar-melhoria-tutor
grupo-pap-aprovacao.atualizar
grupo-pap-aprovacao.reenviar
```

## Rotas cross-tenant de colégios

Fonte: [routes/modules/colegios.php](routes/modules/colegios.php)

```text
colegios.cursos.show
colegios.cursos.classes.show
colegios.cursos.classes.turnos.turmas.show
colegios.cursos.classes.turnos.turmas.pap.show
colegios.cursos.classes.turnos.turmas.pap.definir-data
colegio.grupo-pap-aprovacao.aprovar
colegio.grupo-pap-aprovacao.reprovar
colegio.grupo-pap-aprovacao.solicitar-melhoria
colegio.grupo-pap-aprovacao.aprovar-tutor
colegio.grupo-pap-aprovacao.solicitar-melhoria-tutor
```

## Pautas e exports

Fonte: [routes/modules/pautas.php](routes/modules/pautas.php)

```text
pautas.cursos
pautas.cursos.turmas
pautas.cursos.turmas.pauta
exportar.mini-pauta.disciplina
exportar.pauta
pauta
```

O Wayfinder é gerado em `resources/js/actions` e `resources/js/routes` através de `php artisan wayfinder:generate --with-form --no-interaction`.

---

# 8. Frontend / Inertia

## Tutela e currículo

Fonte base: [resources/js/pages/tenant/cursos-tutelados](resources/js/pages/tenant/cursos-tutelados)

Inclui:

- [index.jsx](resources/js/pages/tenant/cursos-tutelados/index.jsx)
- [create.jsx](resources/js/pages/tenant/cursos-tutelados/create.jsx)
- [edit.jsx](resources/js/pages/tenant/cursos-tutelados/edit.jsx)
- [show.jsx](resources/js/pages/tenant/cursos-tutelados/show.jsx)
- formulários [create.form.jsx](resources/js/pages/tenant/cursos-tutelados/components/forms/create.form.jsx) e [edit.form.jsx](resources/js/pages/tenant/cursos-tutelados/components/forms/edit.form.jsx)
- tabs de professores, turmas e critérios PAP.

Fonte cross-tenant: [resources/js/pages/tenant/colegio/cursos-tutelados](resources/js/pages/tenant/colegio/cursos-tutelados)

- curso/show;
- classes/show;
- turmas/show;
- grupos PAP.

## PAP

Fonte: [resources/js/pages/tenant/cursos-tutelados/classes/turnos/turmas/pap](resources/js/pages/tenant/cursos-tutelados/classes/turnos/turmas/pap)

Páginas:

- `create.jsx`, `edit.jsx`, `show.jsx`, `index.jsx`;
- `tema/create.jsx`;
- `elementos/create.jsx`, `elementos/edit.jsx`;
- `banca/create.jsx`, `banca/edit.jsx`.

Componentes:

- `components/grupo-pap-form.jsx`;
- `components/tabs/tab-aprovacao.jsx`;
- `components/tabs/tab-banca.jsx`;
- `components/tabs/tab-historico.jsx`;
- `components/tabs/tab-integrantes.jsx`;
- `components/grupo-pap-cards.jsx`;
- `components/shared/info-grupo-box.jsx`;
- `components/shared/modal-decisao-aprovacao.jsx`;
- `components/shared/recomendacao-box.jsx`.

A página externa é [colegio/.../pap/show.jsx](resources/js/pages/tenant/colegio/cursos-tutelados/classes/turnos/turmas/pap/show.jsx). Ela recebe `instituicao` como tutora e `colegio` como instituição que mantém os dados. Os parâmetros de rota devem usar `colegio.id`, não `instituicao.id`.

## Pautas

Fonte: [resources/js/pages/tenant/pautas](resources/js/pages/tenant/pautas)

- `cursos/index.jsx`: cursos que a instituição pode consultar;
- `turmas/index.jsx`: turmas do curso e filtro de ano lectivo;
- `index.jsx`: pauta e componentes de tabela/resumos.

## Actions Wayfinder

As actions geradas correspondem aos controllers tenant e às variantes `Tenant/Colegios`, por exemplo:

- [Colegios/GrupoPapController.ts](resources/js/actions/App/Http/Controllers/Tenant/Colegios/GrupoPapController.ts)
- [Colegios/GrupoPapAprovacaoController.ts](resources/js/actions/App/Http/Controllers/Tenant/Colegios/GrupoPapAprovacaoController.ts)
- [Colegios/BancaJuriPapController.ts](resources/js/actions/App/Http/Controllers/Tenant/Colegios/BancaJuriPapController.ts)
- [Colegios/ElementoGrupoPapController.ts](resources/js/actions/App/Http/Controllers/Tenant/Colegios/ElementoGrupoPapController.ts)
- [PautaController.ts](resources/js/actions/App/Http/Controllers/Tenant/PautaController.ts)

---

# 9. Jobs, commands e listeners

## Tutela/PAP

Não existe Job, Listener ou Event específico para sincronizar tutela curricular ou PAP. As operações cross-tenant são síncronas:

```text
request
  -> valida BD central
  -> Tenant::run(callback)
  -> lê/grava BD do colégio
  -> response
```

Existem comandos/jobs gerais do sistema, mas não fazem parte da implementação desta tutela/PAP. A lista geral pode ser consultada em [app/Jobs](app/Jobs), [app/Console/Commands](app/Console/Commands) e [app/Listeners](app/Listeners).

---

# 10. Policies, Gates e autorização

## Policies

- [CursoTuteladoPolicy.php](app/Policies/Tenant/CursoTuteladoPolicy.php): oferta, tutora, professor associado e gestão.
- [GrupoPapPolicy.php](app/Policies/Tenant/GrupoPapPolicy.php): aluno, professor, tutor, coordenador, aprovação e defesa.
- [BancaJuriPapPolicy.php](app/Policies/Tenant/BancaJuriPapPolicy.php): acesso e gestão da banca.
- [ElementoGrupoPapPolicy.php](app/Policies/Tenant/ElementoGrupoPapPolicy.php): elementos e notas após defesa/banca.
- [PautaPolicy.php](app/Policies/Tenant/PautaPolicy.php): pautas próprias e tutor externo através de `curso_tutelado_shared`.
- [TurmaDisciplinaProfessorPolicy.php](app/Policies/Tenant/TurmaDisciplinaProfessorPolicy.php): professores ligados a disciplinas/turmas.

## Gates

Fonte: [AppServiceProvider.php](app/Providers/AppServiceProvider.php)

Inclui Gates como:

```php
Gate::define('pauta.viewAny', [PautaPolicy::class, 'viewAny']);
Gate::define('pauta.view', [PautaPolicy::class, 'view']);
Gate::define('pauta.viewAnyCurso', [PautaPolicy::class, 'viewAnyCurso']);
```

Existe também `Gate::before` para `SuperAdmin`.

## Tutela externa

A autorização precisa acontecer antes de `Tenant::run`. Se for feita depois, `tenancy()->tenant` já representa o colégio e a tutora deixa de ser reconhecida. O padrão correcto é:

```text
1. tenant actual = tutor
2. validar permissão do tutor
3. validar curso_tutelado_shared.status = activo
4. entrar no tenant tutelado
5. operar apenas nos IDs encadeados do curso partilhado
```

---

# 11. Testes

Testes relacionados existentes:

- [CursoTuteladoPolicyTest.php](tests/Feature/CursoTuteladoPolicyTest.php)
- [GrupoPapShowPermissionsTest.php](tests/Feature/GrupoPapShowPermissionsTest.php)
- [TurmaDisciplinaProfessorAccessTest.php](tests/Feature/TurmaDisciplinaProfessorAccessTest.php)
- [TurmaDisciplinaProfessorCreateTest.php](tests/Feature/TurmaDisciplinaProfessorCreateTest.php)
- [PreencherHistoricoServiceTest.php](tests/Feature/PreencherHistoricoServiceTest.php)
- [ConfirmacaoMatriculaServiceTest.php](tests/Feature/ConfirmacaoMatriculaServiceTest.php)
- [InscricaoAnoLectivoTest.php](tests/Feature/InscricaoAnoLectivoTest.php)
- [InscricaoRoleAssignmentTest.php](tests/Feature/InscricaoRoleAssignmentTest.php)

Ainda faltam testes dedicados para:

- publicação idempotente de `curso_tutelado_shared`;
- instituto tutor a ler turma/PAP no tenant colégio;
- colégio impedido de ler dados da tutora;
- aprovação externa e gravação de `utilizador_externo_*`;
- banca e jurados externos;
- exports remotos trimestral, final e recurso;
- isolamento dos IDs de curso/classe/turno/turma/grupo.

Há testes antigos com imports como `App\\Models\\Instituicao` e `App\\Models\\GrupoPap`, enquanto o código actual usa `App\\Models\\Tenant\\...`; por isso a suíte existente pode falhar antes de testar este fluxo.

---

# 12. Estado actual e lacunas

## Implementado

- tutela externa entre tenants com registo central;
- validação de instituto tutor e colégio tutelado;
- publicação/encerramento/troca de tutela;
- navegação tutor -> colégio -> curso -> classe -> turma;
- pautas remotas;
- leitura de grupo PAP remoto;
- campos para actores externos;
- aprovação/reprovação/melhoria com histórico externo;
- data de defesa remota;
- correcção de vários redirects/Wayfinder e parâmetros de rota.

## Parcial ou ainda por terminar

- controllers cross-tenant de banca ainda usam maioritariamente `professor_id` local;
- operações externas de `aprovarTutor`/`solicitarMelhoriaComoTutor` ainda precisam do mesmo tratamento completo;
- `atualizar`, `reenviar`, melhorias e histórico gerais ainda têm rotas/controladores legados;
- folha de aprovação PAP não está preparada para actor/tenant externo;
- exports de pauta existentes ainda não têm uma camada remota própria;
- falta uma política única de contexto externo para evitar duplicação entre controllers;
- faltam testes de isolamento cross-tenant;
- há rotas duplicadas/legadas em `routes/dashboard.php`, `routes/tenant.php` e `routes/modules/colegios.php`.

## Não foi criada uma tabela central de PAP

A decisão actual é manter PAP no tenant que possui os alunos e a turma. `curso_tutelado_shared` resolve a autorização/localização. As colunas externas resolvem a identidade técnica de actors que não podem ser referenciados por FK entre bases.

Se futuramente for necessário consultar PAPs agregados na BD central, será possível criar uma tabela de índice/eventos PAP, mas isso não é necessário para o fluxo transaccional local.

---

# 13. Comandos de validação usados

```bash
php artisan tenants:migrate --tenants=imcl --path=database/migrations/tenant --force --no-interaction
php artisan tenants:migrate --tenants=cua --path=database/migrations/tenant --force --no-interaction
php artisan wayfinder:generate --with-form --no-interaction
vendor/bin/pint --dirty --format agent
npm run build
php artisan route:list --except-vendor
```

A migration de actors externos foi aplicada nos tenants `imcl` e `cua`. O build Vite e as validações PHP/Pint passaram nas alterações realizadas.
