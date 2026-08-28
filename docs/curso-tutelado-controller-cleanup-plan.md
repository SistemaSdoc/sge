# Proposta: Limpeza do `CursoTuteladoController`

## Estado

Proposta para aprovação. Esta fase não altera o controller nem o comportamento da aplicação.

## Objectivo

Deixar `CursoTuteladoController` como um orquestrador fino, preservando:

- as rotas e os nomes de parâmetros actuais;
- o fluxo de tutela própria e tutela externa;
- as autorizações existentes;
- o contrato das páginas Inertia e dos resources;
- a separação central/tenant;
- o comportamento já validado pelos testes cross-tenant.

A limpeza será feita em pequenos passos, com teste depois de cada passo.

## Diagnóstico actual

O controller concentra responsabilidades diferentes:

| Área | Métodos | Problema |
|---|---|---|
| Listagem | `index` | Monta queries, autorização e payload Inertia no mesmo método |
| Formulário | `create`, `edit` | Consulta classes, níveis, cursos e tutores directamente |
| Criação | `store` | Resolve/cria curso, valida duplicidade, cria relações e popula classes |
| Actualização | `update` | Valida inline, troca tutela, actualiza duração e sincroniza classes |
| Consulta | `show` | Contém eager loading extenso e filtro de ano lectivo |
| Ficheiros | `uploadCriteriosPap` | Valida uploads, apaga ficheiros antigos, grava novos e redirecciona |
| Remoção | `destroy` | Verifica turmas, encerra tutela e elimina o curso local |
| Cross-tenant | `store`, `update`, `destroy` | Mistura fluxo local com sincronização central |

Há ainda alguns riscos que a limpeza deve resolver:

1. `update()` usa validação inline em vez de um Form Request dedicado.
2. `uploadCriteriosPap()` usa `Request` genérico em vez de um Form Request dedicado.
3. `store()` e `update()` envolvem operações locais e centrais com conexões diferentes. Isto não é uma transacção distribuída: uma falha depois de uma confirmação central pode deixar estados divergentes.
4. O controller repete a montagem de parâmetros e respostas Inertia.
5. O route model binding não garante sozinho que `CursoTutelado` pertence à `Instituicao` recebida; a policy deve continuar a ser a barreira final e a relação deve ser validada explicitamente.
6. `destroy()` chama `encerrar()` e depois elimina o curso local; a operação precisa de uma política clara para falhas de sincronização.
7. A migration e o serviço já têm regras de encerramento PAP que não devem ser duplicadas no controller.

## Organização proposta

### 1. Form Requests

Criar:

- `app/Http/Requests/Tenant/UpdateCursoTuteladoRequest.php`
- `app/Http/Requests/Tenant/UploadCursoTuteladoDocumentosRequest.php`

Responsabilidades:

- validação de campos;
- autorização básica baseada na rota e no utilizador;
- mensagens de validação;
- regras condicionais para tutela própria/externa;
- validação de uploads por MIME, extensão lógica e tamanho.

`StoreCursoTuteladoRequest.php` permanece como request de criação, mas a validação de tutela pode ser centralizada num Rule ou num serviço de domínio, em vez de depender de `withValidator()` com `app()`.

### 2. Actions de casos de uso

Criar classes invocáveis em `app/Actions/Tenant/CursoTutelado/`:

- `CreateCursoTutelado.php`
- `UpdateCursoTutelado.php`
- `DeleteCursoTutelado.php`
- `UploadCursoTuteladoDocumentos.php`

Cada Action deve:

- receber dependências por constructor injection;
- receber dados já validados;
- executar um único caso de uso;
- devolver um model ou um resultado explícito;
- concentrar transacções locais;
- delegar tutela externa ao `CursoTuteladoSharedService`;
- não construir respostas HTTP nem redirects.

Exemplo de contrato:

```php
final class CreateCursoTutelado
{
    public function __construct(
        private readonly CursoTuteladoSharedService $sharedService,
    ) {}

    public function handle(
        Instituicao $instituicao,
        array $validated,
    ): CursoTutelado {
        // criação local e publicação externa
    }
}
```

Os nomes podem ser ajustados ao padrão final do projecto antes da implementação.

### 3. Services especializados

Manter `CursoTuteladoSharedService` como dono da relação central/tenant, mas separar consultas de escrita:

- `CursoTuteladoCatalogService`: cursos disponíveis, classes, níveis e tutores;
- `CursoTuteladoViewService`: queries e eager loading de `index`, `show` e `edit`;
- `CursoTuteladoSharedService`: somente vínculo cross-tenant e transições de tutela;
- `CursoTuteladoDocumentService` ou Action de upload: ficheiros PAP e manual.

Não criar services apenas para mover código. A extracção só deve acontecer quando houver uma responsabilidade independente e testável.

### 4. Query objects ou scopes

Não introduzir um repositório genérico. Preferir:

- scopes nos models para filtros reutilizados;
- métodos privados pequenos no `CursoTuteladoViewService`;
- Eloquent com `with`, `whereHas` e `when` explícitos.

Possíveis scopes:

```php
CursoTutelado::forInstituicao($instituicao)->withRelationsForYear($anoLectivoId);
```

Só criar estes scopes depois de confirmar que o mesmo filtro é usado por mais de um caso de uso.

## Controller depois da limpeza

O controller deverá ficar aproximadamente com esta forma:

```php
final class CursoTuteladoController extends Controller
{
    public function __construct(
        private readonly CursoTuteladoViewService $views,
        private readonly CreateCursoTutelado $create,
        private readonly UpdateCursoTutelado $update,
        private readonly DeleteCursoTutelado $delete,
        private readonly UploadCursoTuteladoDocumentos $upload,
    ) {}

    public function store(
        StoreCursoTuteladoRequest $request,
        Instituicao $instituicao,
    ): RedirectResponse {
        $cursoTutelado = $this->create->handle($instituicao, $request->validated());

        return to_route(/* rota actual */)->with(/* flash actual */);
    }
}
```

O código acima é apenas o formato alvo. Não deve ser copiado literalmente sem confirmar os tipos de resposta, rotas e contratos existentes.

## Segurança e autorização

### Ordem obrigatória

1. O controller ou Form Request autoriza a operação no tenant actual.
2. A instituição recebida é confirmada como pertencente ao tenant actual.
3. O curso tutelado é confirmado como pertencente à instituição de oferta.
4. Para tutela externa, o `CursoTuteladoSharedService` valida o vínculo central.
5. Só depois é feita a troca de tenant.
6. Dentro do tenant tutelado, só se consultam ou alteram modelos locais já validados pelo encadeamento de IDs.

Nunca mover um `Gate::authorize()` para dentro de uma troca de tenant sem rever o actor e o contexto da autorização.

### Rotas

Manter os nomes actuais durante a limpeza. Avaliar posteriormente `scopeBindings()` apenas onde a hierarquia de binding for compatível com os dois contextos:

- `instituicao -> cursoTutelado` deve ser sempre validado;
- `cursoTutelado -> cursoClasse -> turno -> turma` deve ser validado;
- operações externas que chegam pelo módulo `colegios` não devem usar binding de modelos tenant antes de `Tenant::run()`.

Uma eventual alteração de binding será uma tarefa separada, com testes HTTP dedicados.

### Uploads

A Action de documentos deve:

- aceitar apenas PDFs pelo conteúdo/MIME validado pelo Laravel;
- manter os nomes gerados pelo filesystem;
- apagar o ficheiro antigo somente depois de o novo estar armazenado;
- evitar deixar o model apontar para um caminho que falhou;
- usar o disk configurado, nunca caminhos construídos a partir de input não validado.

## Transacções e consistência cross-tenant

A aplicação tem pelo menos duas conexões: central e tenant. `DB::transaction()` não cria atomicidade entre ambas.

Proposta conservadora:

- transacção local para alterações locais;
- transacção central para o vínculo central;
- ordem definida e documentada;
- validação completa antes da primeira escrita;
- operação idempotente para poder ser repetida após falha;
- logs com IDs do tenant, curso e vínculo em caso de erro;
- teste de falha parcial para confirmar o estado esperado.

Não adicionar filas ou compensações automáticas nesta limpeza sem requisito funcional explícito. Se for necessária consistência distribuída forte, abrir uma tarefa própria para outbox/reconciliação.

## Plano de execução

### Fase 1: Baseline

- guardar o comportamento actual com testes existentes;
- corrigir apenas imports/rotas inválidos que impeçam os testes;
- criar testes unitários dos contratos das Actions antes da extracção.

### Fase 2: Extracção sem mudança de comportamento

- criar `UpdateCursoTuteladoRequest`;
- criar `UploadCursoTuteladoDocumentosRequest`;
- extrair `UploadCursoTuteladoDocumentos`;
- extrair `CreateCursoTutelado`;
- mover `update()` para `UpdateCursoTutelado`;
- mover `destroy()` para `DeleteCursoTutelado`.

Depois de cada passo:

```bash
php artisan test --compact tests/Unit/CrossTenantIsolationTest.php
php artisan test --compact tests/Feature/CursoTuteladoPolicyTest.php
vendor/bin/pint --dirty --format agent
```

### Fase 3: Limpeza de leitura

- extrair queries de `index`, `create`, `edit` e `show`;
- manter resources e props Inertia com o mesmo formato;
- adicionar testes de payload Inertia onde existirem páginas cobertas.

### Fase 4: Segurança de relações

- adicionar testes de instituição/curso incompatíveis;
- testar IDs de outra turma, classe e tenant;
- rever middleware e route binding do módulo `colegios`;
- não alterar os endpoints cross-tenant sem testes HTTP correspondentes.

### Fase 5: Consistência operacional

- testar falhas no vínculo central e na associação local;
- definir logs e mensagens de erro;
- confirmar comportamento de retry/idempotência;
- só depois considerar melhorias de reconciliação.

## Critérios de aceitação

A limpeza só será considerada concluída quando:

- o controller não contiver regras de negócio de criação/actualização/upload;
- não houver validação inline nos métodos que tenham Form Request próprio;
- o comportamento das props Inertia permanecer compatível;
- as policies continuarem a ser aplicadas antes da troca de tenant;
- nenhum endpoint externo resolver modelos no tenant errado;
- os testes de isolamento e autorização passarem;
- `vendor/bin/pint --dirty --format agent` passar;
- `php -l` passar nos PHP alterados;
- `php artisan route:list --except-vendor` carregar sem duplicatas;
- `npm run build` passar após alterações de rotas/frontend;
- não existirem alterações não relacionadas no diff.

## O que não será feito sem aprovação adicional

- renomear `CursoTuteladoShared`;
- alterar nomes de rotas públicas;
- introduzir Repository pattern genérico;
- adicionar dependências;
- mudar o modelo de dados central/tenant;
- trocar transacções por filas/outbox;
- refactorizar controllers PAP, banca ou pautas em simultâneo;
- corrigir todos os testes antigos fora do escopo deste controller.

## Decisão solicitada

Aprovar ou ajustar:

1. Extracção por Actions para escrita.
2. Form Requests dedicados para update e upload.
3. Services separados para leitura e documentos.
4. Manutenção de `CursoTuteladoSharedService` como fronteira cross-tenant.
5. Refactor incremental, com comportamento preservado e testes a cada fase.
