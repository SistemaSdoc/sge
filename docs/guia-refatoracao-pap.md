# Guia de execução: refatoração do módulo PAP

## Objectivo e decisão de organização

Este guia define como separar o módulo PAP por fluxo de negócio, mantendo as convenções Laravel já usadas no projecto:

- Controllers, Form Requests, Resources, Actions, Services, Models e Policies continuam nas respectivas camadas;
- os ficheiros PAP ficam agrupados por domínio dentro de cada camada;
- não introduzimos uma arquitectura paralela como `app/Modules`;
- preservamos as rotas, nomes de rotas, contratos Inertia e comportamento actual durante a migração;
- não alteramos a base de dados nem criamos um modelo/tabela `TemaPap` nesta fase.

O tema escolhido é actualmente parte dos dados do `GrupoPap`. A separação pretendida é primeiro de responsabilidades e fluxos no código. Só se justifica separar o modelo ou a tabela se o domínio passar a precisar de propostas de tema independentes do grupo, várias propostas por grupo ou versões próprias do tema.

## Regra essencial para trabalhar em paralelo

**Construir primeiro a nova implementação sem alterar os ficheiros originais.**

1. Os ficheiros originais continuam a ser a implementação activa e a referência de comportamento.
2. Criamos os novos ficheiros nas pastas PAP abaixo, copiando para lá a lógica existente que pertence a cada fluxo.
3. Durante esta fase não editamos, movemos, renomeamos nem apagamos os originais. Também não mudamos rotas, imports, registo de policies ou outros pontos de entrada para chamar a implementação nova.
4. Cada nova implementação deve ficar completa e ser revista/testada na medida do possível sem substituir o fluxo activo.
5. Só depois de todos os componentes necessários estarem prontos, o responsável pela integração liga os componentes novos às rotas e restantes pontos de entrada.
6. Os originais continuam intactos durante a ligação e a validação. Não os apagar no mesmo passo da integração: mantê-los como referência/fallback até o novo fluxo passar pelas verificações acordadas.

Exemplo: criar `app/Http/Controllers/Tenant/Pap/GrupoPapController.php` copiando para lá a lógica actual de grupo. O agente não modifica `app/Http/Controllers/Tenant/GrupoPapController.php`, nem troca a rota para o novo controller enquanto a implementação e as dependências do fluxo não estiverem prontas.

Esta estratégia é uma migração com implementação paralela, não uma edição directa dos ficheiros de produção. **Não criar um Git worktree adicional** salvo pedido expresso; “worktree” neste guia refere-se à árvore de organização do código.

## Árvore de organização proposta

Os Controllers e Resources ficam directamente na raiz da pasta `Pap`, sem subpastas por fluxo. Cada fluxo tem um ficheiro principal, pelo que subpastas adicionais nesses dois locais criariam níveis sem benefício.

```text
app/
├── Http/
│   ├── Controllers/Tenant/
│   │   ├── Pap/
│   │   │   ├── GrupoPapController.php
│   │   │   ├── TemaPapController.php
│   │   │   ├── AprovacaoTemaPapController.php
│   │   │   ├── TrabalhoPapController.php
│   │   │   ├── DefesaPapController.php
│   │   │   ├── ElementoGrupoPapController.php
│   │   │   └── BancaJuriPapController.php
│   │   └── Colegios/Pap/
│   │       ├── GrupoPapController.php
│   │       ├── AprovacaoTemaPapController.php
│   │       └── TrabalhoPapController.php
│   │
│   ├── Requests/Tenant/Pap/
│   │   ├── Grupo/
│   │   │   ├── StoreGrupoPapRequest.php
│   │   │   └── UpdateGrupoPapRequest.php
│   │   ├── Tema/
│   │   │   ├── DefinirTemaPapRequest.php
│   │   │   ├── CorrigirTemaPapRequest.php
│   │   │   └── ActualizarTemaPapRequest.php
│   │   ├── Aprovacao/
│   │   │   ├── AprovarTemaPapRequest.php
│   │   │   ├── ReprovarTemaPapRequest.php
│   │   │   ├── SolicitarMelhoriaTemaPapRequest.php
│   │   │   └── ReenviarTemaPapRequest.php
│   │   ├── Trabalho/
│   │   │   ├── SubmeterTrabalhoPapRequest.php
│   │   │   └── FeedbackTrabalhoPapRequest.php
│   │   └── Defesa/
│   │       └── DefinirDataDefesaRequest.php
│   │
│   └── Resources/Tenant/Pap/
│       ├── GrupoPapIndexResource.php
│       ├── GrupoPapShowResource.php
│       ├── GrupoPapCreateResource.php
│       ├── GrupoPapEditResource.php
│       ├── TemaPapResource.php
│       ├── TrabalhoPapResource.php
│       ├── BancaJuriPapResource.php
│       └── ElementoGrupoPapResource.php
│
├── Actions/Tenant/Pap/
│   ├── Grupo/
│   │   ├── CreateGrupoPap.php
│   │   ├── UpdateGrupoPap.php
│   │   └── DeleteGrupoPap.php
│   ├── Tema/
│   │   ├── DefinirTemaPap.php
│   │   ├── ActualizarTemaPap.php
│   │   └── ReenviarTemaPap.php
│   └── Defesa/
│       └── DefinirDataDefesa.php
│
├── Services/Tenant/Pap/
│   ├── Consultas/
│   │   ├── ListarGruposPap.php
│   │   ├── PrepararFormularioGrupoPap.php
│   │   └── PrepararDetalheGrupoPap.php
│   ├── Tema/
│   │   └── TemaPapUnicidadeService.php
│   ├── Aprovacao/
│   │   └── AprovacaoTemaService.php
│   ├── Trabalho/
│   │   └── TrabalhoPapService.php
│   └── CrossTenant/
│       └── serviços auxiliares apenas se houver uma responsabilidade cross-tenant própria
│
└── Policies/Tenant/
    └── GrupoPapPolicy.php
```

### Notas sobre a árvore

- Os nomes são uma direcção de organização, não autorização para criar classes vazias ou duplicar a mesma lógica em várias classes.
- `AprovacaoTemaService` e `TrabalhoPapService` já representam fluxos coerentes. Mantê-los como donos das respectivas operações inicialmente; não criar Actions que sejam apenas wrappers para os mesmos métodos.
- Os ficheiros de Form Request podem ficar em subpastas por fluxo porque há vários Requests e as pastas ajudam a localizar cada formulário.
- Os Resources ficam achatados directamente em `Resources/Tenant/Pap`, conforme a decisão para esta refatoração.
- A árvore de `Colegios/Pap` separa o ponto de entrada cross-tenant do ponto de entrada tenant. Os controllers de colégios são adaptadores do contexto; não devem duplicar a regra de negócio que pode ser partilhada com o tenant.
- Banca e elementos já têm Requests, Policies e Actions próprias. Mantê-las inicialmente onde estão é aceitável; não as mover só para uniformizar a árvore. Qualquer migração desses ficheiros deve ser uma tarefa própria, sem alterar o comportamento.
- A organização de Services é indicativa. Não criar um Service para cada método; extrair apenas consultas ou coordenação que tenham uma responsabilidade clara.

## Limites de cada fluxo

### 1. Grupo PAP

**Inclui:** listagem, criação, edição e remoção do grupo; nome, turma, tutor e integrantes; opções e dados necessários aos formulários.

**Não inclui:** definição/aprovação do tema, versões do trabalho, decisões sobre o trabalho ou agendamento da defesa.

Ponto de partida actual:

- `app/Http/Controllers/Tenant/GrupoPapController.php`
- `app/Actions/Tenant/GrupoPap/`
- `app/Services/Tenant/GrupoPap/GrupoPapService.php`
- parte de listagem e opções em `app/Services/Tenant/GrupoPap/GrupoPapViewService.php`

### 2. Tema e proposta

**Inclui:** escolher uma sugestão ou propor tema autoral; validar tema e estudo de caso; definir/corrigir tema, problema e objectivos; submeter ao tutor.

**Não inclui:** aprovar/reprovar pela coordenação ou gerir o documento final do trabalho.

Ponto de partida actual:

- `app/Http/Controllers/Tenant/GrupoPapTemaController.php`
- `app/Services/Tenant/TemaPapUnicidadeService.php`
- `app/Rules/TemaPapUnico.php`
- `app/Rules/EstudoCasoPapUnico.php`
- `app/Http/Requests/Tenant/GrupoPap/ActualizarTemaRequest.php`

As sugestões do curso tutelado são um catálogo separado: `SugestaoTemaPap` pertence ao curso; `tema_grupo` é a proposta seleccionada pelo grupo. Não misturar o CRUD das sugestões com a proposta do grupo.

### 3. Aprovação do tema

**Inclui:** decisões do tutor e da coordenação, pedido de melhoria, reenvio para análise, histórico de decisões e notificações associadas a essas decisões.

**Não inclui:** revisão do PDF final do trabalho; essa aprovação pertence ao fluxo Trabalho.

Ponto de partida actual:

- `app/Http/Controllers/Tenant/GrupoPapAprovacaoController.php`
- `app/Http/Controllers/Tenant/Colegios/GrupoPapAprovacaoController.php`
- `app/Services/Tenant/AprovacaoTemaService.php`
- `app/Models/Tenant/HistoricoAprovacaoPap.php`

### 4. Trabalho PAP

**Inclui:** inicialização após aprovação do tema; submissão e armazenamento privado de versões; revisão pelo tutor e pela coordenação; correcções, feedback, aprovação final e acesso controlado aos ficheiros.

Ponto de partida actual:

- `app/Http/Controllers/Tenant/TrabalhoPapController.php`
- `app/Http/Controllers/Tenant/Colegios/TrabalhoPapController.php`
- `app/Services/Tenant/TrabalhoPapService.php`
- `app/Models/Tenant/TrabalhoPap.php`
- `app/Models/Tenant/TrabalhoPapVersao.php`
- `app/Models/Tenant/TrabalhoPapFeedback.php`

Não mover lógica de armazenamento para o controller. Manter a autorização do download separada da autorização para submeter ou avaliar.

### 5. Defesa, banca e avaliação

**Defesa:** data, hora e local da defesa.

**Banca:** jurados, funções e operações de banca. O módulo já tem controller, Requests, Policy e Actions próprios.

**Elementos:** integrantes e notas individuais. O módulo já tem controller, Requests, Policy e Actions próprios.

Estes fluxos relacionam-se com o grupo, mas têm regras e permissões próprias. Não os misturar no controller geral de Grupo PAP.

## Responsabilidades por camada

### Controllers

Controllers recebem o pedido HTTP, autorizam (ou usam a autorização do Form Request), chamam uma operação, preparam o Resource/payload Inertia e devolvem a resposta. Não devem concentrar queries complexas, regras de estado, transacções, upload/armazenamento ou notificações.

Os controllers PAP ficam directamente na raiz de `Controllers/Tenant/Pap` e `Controllers/Tenant/Colegios/Pap`, sem subpastas por fluxo.

### Form Requests

Criar um Request por intenção de escrita relevante: criar grupo, actualizar grupo, definir/corrigir tema, aprovar/reprovar, solicitar melhoria, reenviar tema, submeter trabalho, enviar feedback e definir defesa.

Cada Request deve:

- validar apenas os campos necessários àquela operação;
- usar `validated()` ou `safe()` ao passar os dados para a Action;
- manter autorização distinta da validação;
- não permitir alterações incidentais de campos de outros fluxos;
- reutilizar as Rules existentes de unicidade e validação em vez de duplicar a regra.

Validação inline continua aceitável para entradas pequenas e pontuais, desde que não duplique um formulário nem contenha regras de negócio significativas.

### Actions e Services

- **Action:** um comando/caso de uso de escrita, por exemplo criar grupo, aprovar tema ou submeter versão. Recebe dados validados, coordena a operação e devolve um resultado explícito.
- **Service:** lógica reutilizável de domínio ou consultas/coordenacão que não pertence a um único endpoint. Evitar Services genéricos que apenas reencaminham chamadas.
- Transacções de escrita devem ficar junto do caso de uso que precisa de atomicidade.
- Notificações devem ser disparadas a partir do caso de uso apropriado, sem fazer o controller conhecer os detalhes dos destinatários.
- Dependências devem ser injectadas pelo construtor/método. Não introduzir `app()`/`resolve()` para dependências que podem ser injectadas normalmente.

### Resources

Resources definem payloads estáveis para Inertia/API. Ficam directamente em `Resources/Tenant/Pap`, sem subpastas.

Não colocar regras de autorização, queries ou efeitos secundários num Resource. Evitar que um Resource dependa implicitamente de `Auth::guard()` se o utilizador necessário puder ser passado de forma explícita segundo o padrão local.

### Policies

`GrupoPapPolicy` tem hoje abilities de grupo, tema, aprovação, defesa e trabalho. Continuar a ser a policy registada para `GrupoPap` durante a migração, mantendo os nomes de abilities e a autorização actual.

Não criar policies adicionais sem planear o registo explícito e a migração de todos os usos. É possível extrair colaboradores pequenos para organizar regras internamente, mas a Policy deve continuar a ser o ponto de entrada compatível até a integração estar concluída.

As policies próprias de banca, elementos e sugestões de temas continuam a aplicar as respectivas permissões.

## Controllers tenant e contexto de colégios

O módulo tem rotas tenant e rotas no módulo de colégios. Os fluxos de colégios atravessam tenants e não são uma cópia simples do caminho local.

Para cada operação cross-tenant, preservar e testar explicitamente:

1. em que tenant os dados do grupo estão armazenados;
2. qual tenant está activo no pedido;
3. a identidade e o tenant do actor que tomou a decisão;
4. que instituição tem autorização para ver ou alterar o grupo;
5. em que contexto ocorre a autorização;
6. em que tenant são criados histórico, versões, feedback e notificações.

Os controllers de colégios devem tratar do contexto, da troca de tenant e da resposta HTTP. Sempre que a operação de negócio for comum, devem chamar a mesma Action/Service, passando explicitamente os dados do actor e do tenant quando necessários. Não duplicar a máquina de estados em dois controllers.

Não mudar URLs, nomes de rotas, estrutura de parâmetros, componentes Inertia nem permissões durante a cópia inicial. A integração pode continuar a usar as rotas actuais e substituir apenas o controller associado quando todos os componentes estiverem prontos.

## Documentação de métodos

Documentar em **português de Portugal**, com PHPDoc curto, claro e funcional:

- cada método novo, público ou privado, deve ter uma descrição simples que explique o que faz;
- parâmetros ou retorno só precisam de `@param`/`@return` quando os tipos ou o contrato não forem óbvios, ou quando for necessário documentar a forma de um array;
- não adicionar comentários que apenas repitam literalmente o nome da instrução;
- preferir nomes claros e unidades pequenas a comentários extensos;
- não usar comentários inline como substituto de documentação.

Exemplo:

```php
/**
 * Regista a decisão da coordenação sobre a proposta do grupo.
 *
 * @param array{comentario?: string|null} $dados
 */
public function handle(GrupoPap $grupoPap, User $actor, array $dados): HistoricoAprovacaoPap
{
    // ...
}
```

## Coordenação dos agentes e prevenção de conflitos

### Antes de começar

O agente deve perguntar explicitamente ao utilizador:

> Qual parte do módulo PAP queres que eu assuma primeiro: Controller, Form Request, Resource, Action/Service, Policy ou testes? Vou trabalhar apenas nessa responsabilidade, criar a nova implementação sem alterar os ficheiros originais e deixar a integração para depois de os componentes estarem prontos.

Se a tarefa recebida já indicar uma parte específica, o agente confirma esse âmbito e lista os ficheiros que pretende criar. Não deve alargar o trabalho às outras camadas sem autorização.

### Uma responsabilidade por agente/tarefa

Pode haver trabalho paralelo, por exemplo:

- agente A: novo controller de grupo;
- agente B: Form Requests de grupo;
- agente C: Resources PAP;
- agente D: Actions do fluxo de grupo/tema;
- agente E: testes de caracterização.

Cada pessoa/agente deve ter uma lista exclusiva de ficheiros. Não atribuir o mesmo ficheiro a dois agentes. Não deixar dois agentes editar o mesmo controller, Request, Resource, rota, Provider ou Policy.

### Contratos antes de paralelizar componentes dependentes

Antes de um controller novo depender de Requests/Actions/Resources ainda em construção, combinar:

- nome e namespace da classe;
- método chamado e assinatura;
- formato dos dados validados;
- tipo/resultado da Action;
- contrato do Resource/payload Inertia;
- quem é responsável pela integração final.

Se o contrato ainda não estiver acordado, o agente deve limitar-se à sua parte e documentar a interface esperada; não criar uma segunda versão especulativa da dependência.

### Integração

Um responsável de integração, designado pelo utilizador, deve:

1. rever os componentes entregues e os contratos;
2. confirmar que cada fluxo está completo e coberto;
3. ligar os novos controllers às rotas existentes e apontar imports/autorização aos novos Requests/Resources/Actions;
4. rever rotas, nomes, bindings e diferenças tenant/cross-tenant;
5. manter os ficheiros originais intactos durante a validação;
6. comparar o comportamento antigo e novo e executar os testes;
7. só propor a remoção dos originais depois da validação e aprovação explícita.

Um agente que não seja o responsável pela integração não deve “ligar tudo” por iniciativa própria.

## Sequência recomendada de execução

### Fase 0 — Baseline e contratos

- Registar o estado do worktree e não sobrescrever alterações de outros colaboradores.
- Identificar rotas, abilities, payloads, notificações e testes de cada fluxo.
- Executar os testes PAP relevantes antes da migração, para distinguir falhas pré-existentes.
- Fixar os contratos entre Controllers, Requests, Actions e Resources.

### Fase 1 — Construção paralela sem ligação

- Criar os novos ficheiros na árvore acima.
- Copiar/adaptar a lógica original para um fluxo de cada vez.
- Manter routes, Providers, policies registadas e controllers originais sem alterações.
- Fazer revisão e testes focados nos novos componentes quando forem testáveis isoladamente.
- Não remover código duplicado enquanto o novo caminho ainda não estiver ligado e validado.

### Fase 2 — Integração controlada, um fluxo de cada vez

Ordem sugerida:

1. consultas e opções dos grupos PAP;
2. CRUD de grupo e integrantes;
3. definição/correcção da proposta e unicidade;
4. decisões e histórico de aprovação do tema;
5. submissão, revisão e aprovação final do trabalho;
6. defesa, banca e avaliação, se estiverem incluídas no âmbito aprovado;
7. pontos de entrada cross-tenant e permissões de colégios.

Em cada passo, trocar apenas o ponto de entrada daquele fluxo, preservar contratos HTTP e executar os testes focados antes de avançar.

### Fase 3 — Verificação e limpeza posterior

- Confirmar todas as rotas e nomes preservados.
- Executar testes de autorização, máquina de estados, tenant e cross-tenant.
- Rever manualmente o diff e confirmar que os originais não foram alterados antes da integração.
- Corrigir divergências encontradas antes de limpar duplicação.
- A remoção/arquivamento dos originais é uma tarefa posterior, explicitamente aprovada; não faz parte da cópia inicial nem é necessária para activar a nova estrutura.

## Testes de caracterização e regressão

Usar os testes existentes como cobertura inicial, sem os apagar:

- `tests/Feature/GrupoPapIndexTest.php`
- `tests/Feature/GrupoPapShowPermissionsTest.php`
- `tests/Feature/GrupoPapDeleteTest.php`
- `tests/Feature/GrupoPapUpdateNameTest.php`
- `tests/Feature/GrupoPapEditOptionsTest.php`
- `tests/Feature/GrupoPapTemaUnicoTest.php`
- `tests/Feature/GrupoPapTutorDecisionPagesTest.php`
- `tests/Feature/CrossTenantGrupoPapVisibilityTest.php`
- `tests/Unit/Tenant/GrupoPapPolicyTest.php`
- testes de elementos, documentos PAP e notificações relacionados com a parte alterada.

Cobrir, conforme o fluxo migrado:

- utilizadores autorizados e não autorizados;
- transições válidas e inválidas de estado;
- unicidade do tema e do estudo de caso;
- isolamento entre grupos/turmas/anos;
- actor local e actor externo;
- tenant de origem e tenant de execução;
- armazenamento e download privado de versões/correcções;
- compatibilidade do payload Inertia e das rotas.

Não adicionar testes genéricos que apenas confirmem que uma classe existe. Os testes devem comprovar o comportamento e as fronteiras de autorização/tenant relevantes.

## Observações a confirmar durante a primeira fase

1. `GrupoPapViewService` mistura listagem local/cross-tenant, filtros, opções de formulário, preparação do detalhe, histórico e dados do trabalho. Deve ser dividido por responsabilidade, mas a lógica de acesso cross-tenant precisa permanecer explícita.
2. `GrupoPapService` e `GrupoPapViewService` têm consultas sobre classes, turnos e turmas com critérios diferentes para identificar a classe PAP. Definir e testar um critério comum antes de consolidar essas consultas.
3. A listagem de pendentes apresenta um contrato que precisa de confirmação: `AprovacaoTemaService::temasPendentesParaCoordenador()` declara receber `User`; o controller tenant foi observado a passar o ID do professor, enquanto o controller de colégios passa o utilizador. Verificar os usos e os testes e corrigir isso numa tarefa isolada antes ou durante a integração desse fluxo.
4. A Policy actual tem abilities de vários fluxos. Preservar todas as abilities e as chamadas existentes na primeira migração; uma divisão posterior não pode deixar abilities sem registo ou sem testes.
5. Há validação extensa inline na definição de tema. Ao movê-la para Form Request, preservar as regras condicionais entre tema sugerido/autoral, professor tutor, estudo de caso e estado do grupo.

## Critérios de conclusão

A refatoração de um fluxo só está pronta para integração quando:

- a nova implementação está nos ficheiros dedicados e tem uma responsabilidade delimitada;
- os métodos estão tipados de acordo com o padrão do projecto e documentados em português de Portugal;
- validação, autorização e efeitos de escrita estão em camadas apropriadas;
- o contrato de rota/resposta está documentado e não mudou sem decisão explícita;
- os testes focados passam ou as falhas pré-existentes estão identificadas;
- nenhum ficheiro original foi alterado durante a fase de construção isolada;
- as dependências com os restantes agentes/componentes estão acordadas;
- o responsável de integração aprovou a ligação antes de alterar os pontos de entrada.
