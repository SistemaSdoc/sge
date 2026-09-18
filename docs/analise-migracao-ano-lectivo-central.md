# Análise da migração do ano lectivo para a central

## Objectivo

Eliminar a tabela `ano_lectivos` de cada base tenant e fazer com que todos os fluxos tenant usem a tabela central como única fonte de verdade.

A referência guardada nos tenants pode continuar a chamar-se `ano_lectivo_id` e continuar a ser um UUID, mas deixa de ser uma foreign key local. O UUID deverá corresponder ao `id` de `central.ano_lectivos`, e toda a validação/leitura do ano deverá consultar a ligação central.

> Importante: copiar o mesmo UUID para uma tabela local não é uma referência central. Enquanto existirem FKs, relações Eloquent ou validações contra `tenant.ano_lectivos`, o sistema continua dependente do ano local.

## Estado actual

### Implementação desta migração

O ciclo de vida central foi reforçado para não depender do nome introduzido manualmente:

- `ano_inicio` é a identidade única do período.
- nome e datas oficiais são calculados pelo serviço central.
- o índice `(ano_inicio, deleted_at)` impede duplicados activos e permite conservar históricos soft-deleted.
- estados e `activo` são definidos pelo serviço de transição/scheduler.
- o próximo ano automático é procurado por `ano_inicio`, não por `nome`.
- anos existentes com referências em tenants não podem ser eliminados.
- um próximo ano manual com datas incompatíveis interrompe a criação automática e gera log para correcção administrativa.

Foi aplicada a primeira migração completa da referência de ano lectivo:

- `App\Services\Central\AnoLectivoService` concentra resolução do ano actual, próximo, último e sincronização de estado na central.
- `AnoLectivoResolverService` tenant passou a delegar na central.
- `CentralAnoLectivoExists` substitui validações contra `tenant.ano_lectivos`.
- Os principais modelos tenant (`Turma`, `Inscricao`, `ClasseTurnoDisciplina`, `RegraAvaliacao`, `Propina`, `ConfirmacaoMatricula`, `TurmaAluno` e `Aluno`) usam o UUID e/ou modelo central.
- Controllers e services tenant que consultavam `Tenant\AnoLectivo` passaram a consultar `Central\AnoLectivo`.
- O comando agendado actualiza apenas estados na tabela central; deixou de inicializar tenants e copiar anos.
- Os seeders de anos foram movidos para `database/seeders/Central/AnoLectivoSeeder.php`.
- Foi criada a migration `2026_09_16_000002_remove_local_ano_lectivo_foreign_keys.php` para remover as FKs locais mantendo temporariamente os UUIDs.
- Foi criada a migration `2026_09_16_000003_drop_local_ano_lectivos_table.php` para eliminar a tabela tenant.
- O model `App\Models\Tenant\AnoLectivo` e o seeder de simulação tenant foram removidos.

A execução das migrations/testes ainda deve ser feita num ambiente com o driver PDO da base usada pelo projecto. Neste ambiente, `pdo_sqlite` está ausente.

Já existe um modelo central e o CRUD central:

- `app/Models/Central/AnoLectivo.php`
- `app/Http/Controllers/Central/AnoLectivoController.php`
- `app/Http/Requests/Central/AnoLectivoRequest.php`
- `database/migrations/2026_09_16_000001_create_central_ano_lectivos_table.php`
- `resources/js/pages/central/anos-lectivos/*`
- Rotas central em `routes/web.php`

A listagem tenant já consulta `App\Models\Central\AnoLectivo` e a rota tenant já é apenas `index`.

Contudo, a maior parte do domínio tenant ainda importa `App\Models\Tenant\AnoLectivo`, usa `AnoLectivo::activo()`, carrega relações locais `anoLectivo` ou valida com `exists:ano_lectivos,id`.

A sincronização actual em `app/Services/Tenant/AnoLectivoConsistencyService.php` ainda copia anos para cada tenant e contém fallback que cria/activa anos localmente. Isso deve desaparecer.

## Modelo alvo

### Central

`central.ano_lectivos` é a única tabela de anos lectivos e contém:

- `id`
- `nome`
- `data_inicio`
- `data_fim`
- `activo`
- `estado`
- `deleted_at`

A central gere o CRUD, activa/encerra anos e define os estados.

### Tenant

As tabelas tenant guardam apenas o UUID central em `ano_lectivo_id`.

Não devem existir:

- tabela tenant `ano_lectivos`;
- FKs tenant para `ano_lectivos`;
- `App\Models\Tenant\AnoLectivo` como modelo de domínio;
- seeders que criem anos por tenant;
- sincronização que copie anos para bases tenant;
- fallback local quando a central está indisponível.

A relação lógica deve ser explicitamente central, por exemplo:

```php
public function anoLectivo(): BelongsTo
{
    return $this->belongsTo(\App\Models\Central\AnoLectivo::class, 'ano_lectivo_id');
}
```

Essa relação deve ser usada apenas quando a versão instalada do Eloquent/Stancl resolver correctamente a ligação `CentralConnection` a partir de um modelo carregado no tenant. Onde a relação cross-connection não for segura, deve-se resolver o nome/dados através de um serviço central por UUID.

## Dependências encontradas

### 1. Persistência e FKs locais

Estas colunas guardam referências ao ano e têm de deixar de apontar para a tabela local:

| Tabela tenant | Coluna | Situação actual | Acção necessária |
|---|---|---|---|
| `classe_turno_disciplina` | `ano_lectivo_id` | FK para `ano_lectivos` | Remover FK; manter UUID central |
| `turmas` | `ano_lectivo_id` | FK para `ano_lectivos` | Remover FK; manter UUID central |
| `inscricoes` | `ano_lectivo_id` | FK para `ano_lectivos` | Remover FK; manter UUID central |
| `regras_avaliacao` | `ano_lectivo_id` | `foreignUuid`, cascade | Remover FK; manter UUID central |
| `propinas` | `ano_lectivo_id` | FK, restrict | Remover FK; manter UUID central |
| `periodo_lancamento_notas` | `ano_lectivo_id` | FK para `ano_lectivos` | Remover FK; manter UUID central |
| `turma_aluno` | `ano_lectivo_id` | FK nullable, cascade | Remover FK; manter UUID central ou eliminar coluna se redundante |
| `confirmacao_matriculas` | `ano_lectivo_atual_id` | FK local | Remover FK; UUID central |
| `confirmacao_matriculas` | `ano_lectivo_proximo_id` | FK local | Remover FK; UUID central |

Migrations de origem identificadas:

- `database/migrations/tenant/0002_06_01_122213_create_instituicoes_table.php`
- `database/migrations/tenant/2026_06_01_125641_create_professores_alunos_table.php`
- `database/migrations/tenant/2026_06_01_125720_create_inscricoes_table.php`
- `database/migrations/tenant/2026_07_20_082106_create_regras_avaliacao_table.php`
- `database/migrations/tenant/2026_07_29_123738_create_propinas_table.php`
- `database/migrations/tenant/2026_07_31_104251_create_periodo_lancamento_notas_table.php`
- `database/migrations/tenant/2026_08_01_072923_add_ano_lectivo_id_to_turma_aluno_table.php`
- `database/migrations/tenant/2026_08_01_001124_create_confirmacao_matriculas_table.php`

A migração de produção deve primeiro confirmar que todos os UUIDs existentes nos tenants existem na central. Só depois deve remover constraints e a tabela local.

### 2. Modelos tenant

Modelos que actualmente expõem relações ou campos de ano:

- `app/Models/Tenant/Turma.php`
- `app/Models/Tenant/Inscricao.php`
- `app/Models/Tenant/ClasseTurnoDisciplina.php`
- `app/Models/Tenant/RegraAvaliacao.php`
- `app/Models/Tenant/Propina.php`
- `app/Models/Tenant/PeriodoLancamentoNotas.php`
- `app/Models/Tenant/TurmaAluno.php`
- `app/Models/Tenant/ConfirmacaoMatricula.php`
- `app/Models/Tenant/Aluno.php`

Acções:

1. Trocar `use App\Models\Tenant\AnoLectivo` por `App\Models\Central\AnoLectivo` quando a relação Eloquent for suportada.
2. Rever `TurmaAluno::anoLectivo()`: actualmente a relação usa `hasOneThrough` via `Turma` e ignora o valor próprio de `turma_aluno.ano_lectivo_id`.
3. Rever `ConfirmacaoMatricula::anoLectivoAtual()` e `anoLectivoProximo()` para apontarem para o modelo central.
4. Manter os nomes públicos `anoLectivo`, `anoLectivoActual` e `anoLectivoId` para reduzir impacto no frontend, mas garantir que os dados vêm da central.
5. Criar um serviço único para resolver ano por ID, activo, próximo e lista, em vez de repetir queries a modelos tenant.

### 3. Resolver e contexto global

`app/Services/Tenant/AnoLectivo/AnoLectivoResolverService.php` consulta actualmente o modelo tenant.

Deve passar a:

- consultar `App\Models\Central\AnoLectivo`;
- resolver o ano activo central;
- resolver o próximo ano central por `data_inicio`;
- não criar, activar ou corrigir anos localmente;
- falhar explicitamente se a central estiver indisponível, em vez de continuar com estado divergente.

O contexto partilhado de Inertia em `app/Http/Middleware/HandleInertiaRequests.php` actualmente não centraliza `anosLectivos`/`anoLectivoId`. Deve ser decidido um contrato único para as páginas tenant:

- `anosLectivos`: lista central disponível para selects;
- `anoLectivoId`: ID central seleccionado/default;
- opcionalmente `anoLectivoActual`: objecto central resumido.

Isto evita que cada controller monte listas diferentes.

### 4. Controllers tenant que precisam de alteração

Os seguintes controllers consultam directamente o modelo local ou dependem de relações locais:

- `app/Http/Controllers/Tenant/AlunoController.php`
- `app/Http/Controllers/Tenant/ClasseTurnoDisciplinaController.php`
- `app/Http/Controllers/Tenant/ClasseTurnoTurmaController.php`
- `app/Http/Controllers/Tenant/Colegios/ClasseTurnoTurmaController.php`
- `app/Http/Controllers/Tenant/Colegios/CursoClasseController.php`
- `app/Http/Controllers/Tenant/Colegios/GrupoPapController.php`
- `app/Http/Controllers/Tenant/Colegios/BancaJuriPapController.php`
- `app/Http/Controllers/Tenant/GrupoPapController.php`
- `app/Http/Controllers/Tenant/InscricaoController.php`
- `app/Http/Controllers/Tenant/InstituicaoCurso/TurmaDisciplinaProfessorController.php`
- `app/Http/Controllers/Tenant/PautaController.php`
- `app/Http/Controllers/Tenant/PeriodoLancamentoNotasController.php`
- `app/Http/Controllers/Tenant/PagamentoController.php`
- `app/Http/Controllers/Tenant/ProfessorController.php`
- `app/Http/Controllers/Tenant/ProgressaoController.php`
- `app/Http/Controllers/Tenant/RegraAvaliacaoController.php`
- `app/Http/Controllers/Tenant/RelatorioController.php`
- `app/Http/Controllers/Tenant/TurmaController.php`
- `app/Http/Controllers/Tenant/BancaJuriPapController.php`
- `app/Http/Controllers/Tenant/CertificadoController.php`
- `app/Http/Controllers/Tenant/ExportarPautaController.php`

Padrões a substituir:

- `use App\Models\Tenant\AnoLectivo`;
- `AnoLectivo::activo()`;
- `AnoLectivo::all()`/`get()`/`find()`/`where()`;
- `whereHas('anoLectivo')` quando a relação ainda aponta para o modelo local;
- carregamentos `with('anoLectivo')` que dependem da tabela tenant;
- `exists:ano_lectivos,id` sem ligação central;
- fallback para o ano mais recente local.

Filtros por `turmas.ano_lectivo_id`, `inscricoes.ano_lectivo_id` e outros campos podem continuar iguais, porque com a migração dos UUIDs para os IDs centrais o filtro passa a usar a referência central. O que muda é a origem/validação do UUID, não necessariamente o nome da coluna.

### 5. Services e regras de negócio

Serviços que têm dependências directas ou indirectas:

- `app/Services/Tenant/InscricaoService.php`
- `app/Services/Tenant/Turma/TurmaService.php`
- `app/Services/Tenant/GrupoPap/GrupoPapService.php`
- `app/Services/Tenant/GrupoPap/GrupoPapViewService.php`
- `app/Services/Tenant/ConfirmacaoMatriculaViewService.php`
- `app/Services/Tenant/Core/RegraAcademica/RegraAplicavel/RegraAplicavelResolver.php`
- `app/Services/Tenant/GrelhaCurricularService.php`
- `app/Services/Tenant/DeclaracaoComNotaService.php`
- `app/Services/Tenant/DeclaracaoSemNotaService.php`
- `app/Services/Tenant/NotaService.php`
- `app/Services/Tenant/PreencherHistoricoService.php`
- `app/Services/Tenant/VerificadorPropinaService.php`
- `app/Services/Tenant/RelatorioService.php`
- `app/Services/Tenant/AnoLectivo/AnoLectivoResolverService.php`

Atenção especial:

- `InscricaoService` deve receber/validar um ID central e persistir esse ID.
- `ConfirmarMatricula` deve resolver actual/próximo na central e validar as turmas pelo UUID central.
- `GrupoPapViewService` actualmente converte IDs para nomes localmente; essa consulta deve ser central.
- `RelatorioService` e `NotaService` não podem usar o ano activo tenant.
- `RegraAplicavelResolver` deve tratar o ID como referência central, mesmo que a regra viva na base tenant.
- `TurmaAluno` e `PreencherHistoricoService` precisam de uma decisão explícita sobre a coluna duplicada.

### 6. Requests e validações

Validações encontradas que consultam a tabela local:

- `app/Http/Requests/Tenant/Inscricao/StoreInscricaoRequest.php`
- `app/Http/Requests/Tenant/InstituicaoCurso/StoreProfessorRequest.php`
- `app/Http/Controllers/Tenant/PropinaController.php`
- `app/Http/Controllers/Tenant/ProgressaoController.php`

Substituir `exists:ano_lectivos,id` por uma regra que use a ligação central, por exemplo:

- `Rule::exists('central_connection.ano_lectivos', 'id')`, se a configuração de conexão suportar esse formato no projecto;
- ou uma `CentralAnoLectivoExists`/`CentralAnoLectivoRule` que execute `App\Models\Central\AnoLectivo::query()->whereKey($value)->exists()`;
- ou `Rule::exists(AnoLectivo::query()->getModel()->getTable(), 'id')->using(...)` conforme o padrão suportado pela versão instalada.

A regra customizada é a opção mais clara para não depender de sintaxe de conexão em migrations/requests e para rejeitar anos soft-deleted quando necessário.

Também deve ser validado que o ano pertence ao estado permitido do fluxo: por exemplo, confirmação de matrícula pode exigir o próximo ano central e não apenas um UUID existente.

### 7. Sincronização, comandos e seeders

`app/Services/Tenant/AnoLectivoConsistencyService.php` deve ser removido ou convertido num serviço que apenas verifica referências, sem criar dados tenant.

Eliminar do fluxo:

- cópia do ano central para `tenant.ano_lectivos`;
- desactivação de anos locais;
- criação do ano inicial local;
- `garantirProximoAno()` local;
- fallback para a lógica local.

`app/Console/Commands/SincronizarAnoLectivoCommand.php` e `routes/console.php` devem deixar de sincronizar anos para cada tenant. Podem ser substituídos por um comando central de manutenção que:

- calcula/actualiza o estado dos anos na central;
- verifica referências órfãs em cada tenant;
- reporta tenants com UUIDs inexistentes;
- não cria ou actualiza anos nas bases tenant.

Seeders a adaptar/remover:

- `database/seeders/Tenant/AnoLectivoSeeder.php`
- `database/seeders/Tenant/AnosLectivosSimulacaoSeeder.php`
- `database/seeders/Tenant/InscricaoSeeder.php`
- `database/seeders/Tenant/TurmaSeeder.php`
- `database/seeders/Tenant/TenantDatabaseSeeder.php`

Os seeders tenant devem obter anos através do modelo central. O seeder central deve criar os anos de teste antes do seeding dos tenants.

### 8. Frontend tenant

Os componentes não precisam necessariamente de mudar o nome do contrato: `ano_lectivo_id` pode continuar a transportar o UUID central.

Devem ser confirmados e testados:

- `resources/js/pages/tenant/alunos/*`
- `resources/js/pages/tenant/inscricoes/*`
- `resources/js/pages/tenant/turmas/*`
- `resources/js/pages/tenant/pap/*`
- `resources/js/pages/tenant/pautas/*`
- `resources/js/pages/tenant/cursos-tutelados/*`
- `resources/js/pages/tenant/preencher-historico/*`
- `resources/js/pages/tenant/anos-lectivos/*`

O frontend deve receber sempre `anosLectivos` derivados da central. Não deve assumir que a lista local é completa ou que o ano activo foi sincronizado para o tenant.

A mudança deve ser transparente para os selects se o formato continuar `{ id, nome }`, mas os testes devem confirmar que os IDs apresentados são IDs existentes na tabela central.

### 9. Policies e menu

`app/Policies/Tenant/AnoLectivoPolicy.php` ainda referencia o modelo tenant. Como o tenant só lista:

- trocar o modelo para o central, ou remover a policy de mutações;
- manter apenas a autorização de visualizar a lista, se essa permissão continuar necessária;
- remover qualquer expectativa de `create`, `update` ou `delete` no tenant.

`app/Services/Tenant/Menu/SidebarMenuService.php` pode continuar a mostrar a listagem, mas a autorização deve ser independente de um modelo local.

## Plano de execução recomendado

### Fase 0: contrato e inventário

1. Confirmar que todos os anos históricos/futuros usados nos tenants existem na central.
2. Exportar/relacionar `tenant.ano_lectivos` com `central.ano_lectivos` por UUID e por nome/período.
3. Detectar UUIDs órfãos, nomes divergentes e datas divergentes.
4. Decidir se `turma_aluno.ano_lectivo_id` é necessário ou se é sempre derivável de `turma.ano_lectivo_id`.
5. Definir comportamento para anos soft-deleted que ainda tenham dados históricos.

### Fase 1: camada central única

1. Criar `CentralAnoLectivoService` ou consolidar o resolver numa camada central.
2. Fazer todos os resolvers e listas tenant usarem `App\Models\Central\AnoLectivo`.
3. Criar regra de validação central reutilizável.
4. Trocar relações Eloquent dos modelos tenant para o modelo central, onde suportado.
5. Adicionar testes para activo, próximo, histórico, UUID inválido e ano soft-deleted.

### Fase 2: retirar integridade local

1. Criar migrations tenant para remover todas as FKs para `ano_lectivos`.
2. Manter os campos UUID e os índices compostos necessários.
3. Corrigir regras `cascadeOnDelete`/`restrictOnDelete`, pois já não haverá FK local para executar esses efeitos.
4. Implementar explicitamente as regras de negócio que antes dependiam do cascade/restrict.
5. Remover a tabela tenant `ano_lectivos` apenas depois de todos os acessos terem sido migrados.

### Fase 3: remover sincronização e seed local

1. Remover fallback e criação local de `AnoLectivoConsistencyService`.
2. Remover o comando/schedule de cópia para tenants.
3. Alterar seeders para usar a central.
4. Adicionar comando de auditoria de referências órfãs.

### Fase 4: adaptar todos os fluxos

Migrar e testar por grupos:

1. Ano actual, dashboard e contexto global.
2. Turmas, classes, disciplinas e grelha curricular.
3. Inscrições, alunos e confirmações de matrícula.
4. Notas, pautas e períodos de lançamento.
5. Propinas e pagamentos.
6. Regras de avaliação.
7. PAP, cursos tutelados e acesso cross-tenant.
8. Progressão e preenchimento de histórico.
9. Relatórios, declarações, certificados e exportações.
10. Professores e associações de disciplinas.

### Fase 5: remoção e verificação final

1. `rg` sem resultados para `App\\Models\\Tenant\\AnoLectivo`.
2. `rg` sem queries tenant a `ano_lectivos`.
3. `rg` sem `exists:ano_lectivos,id` em requests/controllers tenant.
4. Nenhuma migration tenant cria a tabela `ano_lectivos` em instalações novas.
5. Teste de criação de tenant confirma que nenhuma tabela local de ano é criada.
6. Teste multi-tenant confirma que dois tenants usam o mesmo ID central e vêem o mesmo nome/estado.
7. Teste de alteração central confirma que todos os tenants passam a mostrar o novo estado sem job de cópia.
8. Teste de remoção/soft delete central confirma o comportamento esperado para dados históricos.

## Testes necessários

Além dos testes existentes, criar/actualizar testes para:

- listagem tenant baseada na central;
- resolver do ano activo e próximo baseado na central;
- criação de turma com UUID central;
- criação de inscrição com UUID central;
- validação de UUID central inexistente;
- confirmação de matrícula actual/próxima;
- progressão entre anos centrais;
- regras de avaliação e períodos de notas;
- propinas por ano central;
- PAP e filtros cross-tenant;
- relatórios, declarações e exportações;
- ausência da tabela tenant `ano_lectivos`;
- ausência de FKs locais para essa tabela.

Os testes existentes que assumem `App\Models\Tenant\AnoLectivo` devem ser migrados, especialmente:

- `tests/Feature/AnoLectivoConsistencyServiceTest.php`
- `tests/Feature/AlunoFichaMatriculaTest.php`
- `tests/Feature/ConfirmacaoMatriculaIncompleteStatusTest.php`
- `tests/Feature/ConfirmacaoMatriculaServiceTest.php`
- `tests/Feature/ConfirmarMatriculaActionTest.php`
- `tests/Feature/GrupoPapIndexTest.php`
- `tests/Feature/InscricaoAnoLectivoTest.php`
- `tests/Feature/InscricaoRoleAssignmentTest.php`
- `tests/Feature/NotaServicePeriodoDisponibilidadeTest.php`
- `tests/Feature/PeriodoLancamentoNotasControllerTest.php`
- `tests/Feature/PreencherHistoricoServiceTest.php`
- `tests/Feature/RegraAcademica/RegraAplicavelTest.php`
- `tests/Unit/CrossTenantIsolationTest.php`
- `tests/Unit/DeclaracaoAnoLectivoTest.php`
- `tests/Unit/ProgressaoControllerTest.php`

## Riscos e decisões que precisam de confirmação

1. **Cross-connection Eloquent:** `belongsTo` de um modelo tenant para `Central\AnoLectivo` precisa de ser validado com a versão actual do Laravel/Stancl. Se não funcionar de forma previsível, usar um resolver/service central em vez de relações automáticas.
2. **Soft deletes:** apagar um ano central não pode quebrar dados históricos sem uma política clara. Provavelmente anos com utilização devem ser arquivados, não eliminados.
3. **Cascades locais:** ao remover FKs, cascades deixam de existir. As regras de exclusão devem ser proibidas ou implementadas explicitamente antes de apagar dados dependentes.
4. **IDs órfãos:** qualquer UUID local sem correspondente central bloqueia a remoção da tabela local até ser corrigido ou mapeado.
5. **Estado activo:** só a central pode decidir qual ano está activo. O tenant deve apenas ler esse estado.
6. **Disponibilidade central:** uma indisponibilidade da central deve produzir erro controlado/estado indisponível, nunca criar um ano local alternativo.
7. **Dados cross-tenant:** serviços que consultam outros tenants não devem tentar resolver o ano na base do tenant errado; o nome/estado deve vir sempre da central.

## Critério de conclusão

A migração só está concluída quando o tenant puder ser instalado sem a tabela `ano_lectivos`, todos os UUIDs guardados nas tabelas tenant forem validados contra a central, nenhum serviço tenant importar o modelo local, e uma alteração no central for imediatamente reflectida em todos os tenants sem sincronização de cópia.
