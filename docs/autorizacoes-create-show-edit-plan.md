# Plano de cobertura de autorização: create, show e edit

## Resumo da decisão

Sim, faz sentido esconder ou desativar certos elementos nesses fluxos, mas com uma distinção importante:

- O controlo real de acesso deve sempre ficar no backend.
- O frontend só deve esconder/ocultar elementos para melhorar a experiência e evitar navegação desnecessária.
- Nunca depender apenas do frontend para restringir acesso, porque o utilizador consegue contornar isso com uma URL direta ou uma chamada manual.

## Regra geral

### 1) Create
Recomendação: sim, esconder a entrada para criar.

Porquê:
- Se o utilizador não tem permissão para criar, o botão “Novo”/“Adicionar” deve desaparecer ou ficar desativado.
- A rota de criação também deve ser protegida no backend com a policy correspondente (`create`).

Aplicação sugerida:
- Lista de recursos: esconder o botão de criação quando `can.create` for `false`.
- Backend: bloquear o acesso a `create()`/`store()` com `authorize('create', Model::class)` ou via `authorizeResource`.

### 2) Show
Recomendação: sim, esconder navegação para ver detalhes quando não há permissão de leitura.

Porquê:
- Se o utilizador não pode ver o recurso, não deve ver links para o detalhe nem abrir a página diretamente.
- A página de detalhe pode continuar a existir como uma view “apenas leitura” caso o utilizador tenha `view`, mas sem `update` deve aparecer como leitura sem ações de edição.

Aplicação sugerida:
- Na lista: esconder o clique para abrir o detalhe ou esconder o botão/ícone de visualização quando `can.view` for `false`.
- Backend: bloquear `show()` com `authorize('view', $model)`.
- Se houver botões de editar/eliminar na página de detalhe, eles devem aparecer apenas quando as permissões respectivas existirem.

### 3) Edit
Recomendação: sim, esconder a entrada para editar e bloquear a rota.

Porquê:
- Editar é uma ação de escrita, então só deve ficar disponível para quem tem `update`.
- A rota `edit()` e `update()` devem estar protegidas no backend.

Aplicação sugerida:
- Na lista: esconder o botão/ícone de editar quando `can.update` for `false`.
- Na página de detalhe: esconder o botão de editar quando `can.update` for `false`.
- Backend: bloquear `edit()` e `update()` com `authorize('update', $model)`.

## O que esconder exatamente

### Em listas/index
Idealmente esconder ou desativar:
- botão “Novo” / “Criar” quando não houver `create`;
- ícones/ações de visualizar, editar e eliminar conforme cada permissão;
- clique na linha quando o utilizador não tem `view`.

### Em páginas de detalhe/show
Idealmente esconder ou desativar:
- botão de editar quando não houver `update`;
- botão de eliminar quando não houver `delete`;
- links para ações que não fazem sentido para o utilizador.

### Em formulários/edit
Idealmente:
- não mostrar o formulário se o utilizador não tem `update`;
- se o formulário for carregado por engano, o backend deve devolver 403.

## O que não devemos esconder apenas por UX

Não devemos depender só do frontend para esconder dados sensíveis. O backend deve continuar a garantir que:
- utilizadores sem `view` não conseguem aceder ao recurso;
- utilizadores sem `update` não conseguem submeter alterações;
- utilizadores sem `delete` não conseguem eliminar.

## Recomendação para este projeto

A abordagem mais consistente para este projeto é:

1. Manter o backend como fonte de verdade.
2. Continuar a passar flags como `can.view`, `can.create`, `can.update`, `can.delete` para o Inertia.
3. Usar essas flags no React para esconder botões, links e ações.
4. Não tentar esconder dados “à força” no frontend sem também proteger no backend.

## Onde aplicar neste projeto

### Backend
- Controllers dos recursos já usam `authorizeResource` ou autorizações explícitas em métodos de leitura/escrita.
- Para os recursos que ainda não tenham esse padrão consistente, devemos reforçar isso em:
  - `create()` / `store()`;
  - `show()`;
  - `edit()` / `update()`;
  - `destroy()`.

### Frontend
- Nos componentes de lista, aplicar a lógica de visibilidade com base nos `can` recebidos do backend.
- Nos componentes de detalhe, esconder botões de ação conforme as permissões.
- Nos formulários, não renderizar o botão de submissão quando não houver `update`/`create`.

## Implementação sugerida (após aprovação)

1. Cobrir `create` no frontend e no backend.
2. Cobrir `show` no frontend e no backend.
3. Cobrir `edit`/`update` no frontend e no backend.
4. Repetir a mesma lógica para os recursos já tratados (`instituicoes`, `cursos`, `classes`, `turnos`, `turmas`, `pautas`).

## Conclusão

A recomendação é clara:
- sim, devemos esconder ou desativar elementos em `create`, `show` e `edit`;
- mas isso deve ser complementar ao controlo de autorização real no backend;
- o backend é o mecanismo obrigatório de proteção;
- o frontend serve para reduzir confusão e evitar ações impossíveis.
