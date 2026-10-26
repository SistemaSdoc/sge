# Matriz de acesso padrão: role Aluno

Data: 2026-09-25

## Conclusão rápida

O aluno abre o próprio grupo PAP pelo item **Meu Grupo PAP** na sidebar; não deve receber acesso à listagem geral. A policy nega `viewAny` para Aluno, e o link individual só aparece quando existe grupo associado.

O calendário é intencionalmente acessível a todos os usuários autenticados do tenant. A rota de horários agora tem autorização server-side, e as mutações PAP relevantes validam abilities e pertença ao grupo.

## Matriz do fluxo

| Área | Verificação actual | Estado para Aluno |
|---|---|---|
| Dashboard | `DashboardController` detecta role `Aluno` | Funciona |
| Minhas notas | `NotaPolicy::viewAny`: `notas.viewAny` + role `Aluno` | Funciona |
| Grelha curricular | gate `grelha-curricular.viewAny` e policy exige `grelha.viewAny` + role `Aluno` | Funciona |
| Calendário | disponível a usuários autenticados do tenant por decisão de produto | Funciona conforme esperado |
| Horários | rota GET exige `can:horarios.viewAny`; policy permite `Aluno`/`Professor` | Protegido no servidor |
| Listagem PAP | `GrupoPapPolicy::viewAny` nega explicitamente para Aluno | Negado intencionalmente |
| Grupo PAP próprio | sidebar aponta directamente para o grupo e `GrupoPapPolicy::view` valida pertença | Funciona sem listar outros grupos |
| Definir tema | permission `grupopap.definirTema` + pertença + estado editável | Funciona |
| Corrigir tema | permission `grupopap.corrigirTema` + pertença + estado editável | Funciona |
| Submeter trabalho | pertença ao grupo + trabalho em estado submetível | Funciona sem permission adicional |
| Ver/download trabalho | pertença ao grupo | Funciona sem permission adicional |
| Aprovar/corrigir como tutor ou coordenação | tutor ou `grupopap.aprovar` | Negado ao aluno |
| Banca e elementos PAP | permissions de banca/elementos | Negado ao aluno |

## Permissões mínimas recomendadas

O role `Aluno` deve ficar, no mínimo, com:

```text
notas.viewAny
grelha.viewAny
grupopap.definirTema
grupopap.corrigirTema
```

`grupopap.viewAny` não deve ser atribuída ao aluno. `grupopap.view` também não é necessária no ramo de aluno de `GrupoPapPolicy::view()`, que usa pertença ao grupo; pode ser removida do seeder após confirmar que não há outro uso directo.

O aluno não deve receber `grupopap.create`, `grupopap.update`, `grupopap.delete`, `grupopap.definirData`, `grupopap.aprovar`, `grupopap.reprovar`, `grupopap.solicitarMelhoria`, `grupopap.selecionarInstituicao`, `grupopap.selecionarAnoLectivo`, qualquer `elementogrupopap.*`, qualquer `bancajuripap.*`, `notas.create/update/export`, `pautas.*`, `alunos.*`, `turmas.*`, `inscricoes.*`, `acessos.*`, `usuarios.*`, pagamentos, documentos ou permissões administrativas.

As ações de submeter trabalho e descarregar versões são actualmente protegidas por pertença ao grupo, não por permission. Isso é adequado para o fluxo do aluno, desde que a policy continue a exigir a pertença.

## Correções aplicadas

### 1. Listagem PAP bloqueada

`GrupoPapPolicy::viewAny()` nega explicitamente a listagem geral para Aluno. A sidebar mostra o item apenas se `GrupoPapNavigationService` encontrar o grupo do próprio aluno e constrói um link directo para ele.

O aluno não recebe `grupopap.viewAny`.

### 2. Calendário sem autorização específica

O calendário foi confirmado como recurso de leitura para todos os usuários autenticados do tenant. A ausência de uma permission exclusiva é intencional.

As rotas continuam dentro do grupo autenticado do tenant.

### 3. Horários protegidos somente no menu

Foi criada uma rota GET explícita para horários com middleware `can:horarios.viewAny`, usando a policy que permite Aluno e Professor.

O item do menu e o endpoint agora aplicam a mesma autorização.

### 4. Atualização de tema PAP sem autorização explícita

`atualizar()` e `reenviar()` agora verificam abilities específicas; `editar()`, `historico()` e `melhorias()` também exigem autorização. A lista de melhorias filtra grupos pela instituição local do usuário.

Para Aluno, actualizar/re-enviar o tema exige ser membro do grupo e satisfazer a permissão e o estado de correção. Os controllers do fluxo PAP usam explicitamente o guard `tenant`.

### 5. Guard tenant nos controllers do fluxo aluno/PAP

Notas, grelha, Grupo PAP, tema, trabalho e mutações de aprovação agora autorizam com o usuário resolvido pelo guard `tenant`, evitando dependência do guard padrão.

## Estado após esta correção

Implementado:

- a rota de horários passou a existir como rota GET explícita e exige `can:horarios.viewAny`;
- os controllers de notas, grelha e PAP passaram a autorizar pelo guard `tenant`;
- `GrupoPapPolicy` foi registrada explicitamente no provider;
- alunos continuam impedidos de acessar a listagem geral PAP;
- atualização e reenvio de tema PAP passaram a exigir abilities próprias;
- edição, histórico e lista de melhorias PAP passaram a exigir autorização;
- a lista de melhorias passou a filtrar grupos da instituição do usuário;
- foram adicionados testes para impedir aluno não integrante de alterar ou reenviar tema.

Validação: 28 testes focados passaram, Pint passou, PHP não encontrou erros de sintaxe e `git diff --check` passou.

Persistem diagnósticos anteriores de views Inertia inexistentes, rota PAP inexistente e chamadas a `Storage::download()` não reconhecidas pelo analisador em controllers PAP. Esses pontos devem ser tratados numa etapa própria de saneamento do módulo PAP.