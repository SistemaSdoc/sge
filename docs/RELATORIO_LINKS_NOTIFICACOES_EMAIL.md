# Relatório: links das notificações por email

**Data da análise:** 12/11/2026  
**Âmbito:** notificações Laravel, respetivos emissores e templates de email, rotas tenant e configuração de tenancy.

## Resumo executivo

Foi encontrada uma causa comum para links de emails tenant apontarem para `localhost`, para o domínio central ou para o tenant errado: vários emissores construíam URLs com `route()`/`url()` sem fixar o domínio do destinatário. Outros caminhos forçavam `http://` ou recorriam a um domínio fallback.

Foi implementado um gerador partilhado em [TenantUrlGenerator](../app/Services/Tenant/TenantUrlGenerator.php) e os fluxos tenant de email identificados foram migrados para essa regra. O gerador usa o domínio explícito do destinatário ou, quando apropriado, o domínio do tenant ativo; usa o esquema de `config('app.url')` e falha explicitamente se não conseguir determinar domínio ou esquema, em vez de devolver um URL central/localhost.

O caso relatado de PAP foi confirmado no código e corrigido: os coordenadores e membros da tutora recebem a rota cross-tenant do PAP no domínio da tutora; os alunos e o tutor do colégio continuam com a sua notificação própria. A correção está coberta por teste de regressão, mas a migração de tenant usada pela fixture exige MySQL e impediu a execução das assertions no SQLite disponível.

## Regra comum implementada

[TenantNotificationUrl](../app/Notifications/Concerns/TenantNotificationUrl.php) delega a geração para [TenantUrlGenerator](../app/Services/Tenant/TenantUrlGenerator.php). O helper:

- recebe o nome da rota, parâmetros e opcionalmente o domínio específico do destinatário;
- resolve o domínio do tenant ativo apenas quando não foi fornecido um domínio explícito;
- constrói a rota tenant no domínio resolvido e normaliza o esquema com `config('app.url')`;
- lança `LogicException` se faltar domínio ou esquema, sem fallback silencioso para o domínio central.

Foram migradas para este padrão as notificações com links tenant de PAP, cursos tutelados, pautas, professor/planificação, login, perfil, password e tutela, além dos fluxos de ativação e criação de tenant. Os fluxos cross-tenant de submissão do trabalho e validação do tema PAP passam o domínio da tutora explicitamente. A notificação de criação de utilizador também deixou de ser enviada duas vezes.

## Achados e estado

| Prioridade | Estado | Fluxo | Conclusão |
|---|---|---|---|
| P1 | Corrigido | PAP, cursos tutelados e pautas | As notificações que antes usavam `route()` no host ambiente agora usam o domínio tenant resolvido pelo helper. Abrange os grupos PAP, as cinco notificações de sugestões de curso tutelado e as notificações de pauta/solicitação de edição. |
| P1 | Corrigido; teste de integração bloqueado | Tema PAP validado pelo tutor → tutora | Coordenadores e membros da tutora recebem a rota cross-tenant `tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show` no domínio da tutora. O fluxo de submissão do trabalho PAP usa a mesma regra. |
| P1 | Corrigido | Reposição de password e perfil incompleto | Removidos os URLs que forçavam HTTP ou produziam host vazio. A rota usa o esquema configurado e domínio tenant; a falta de domínio agora é erro explícito. |
| P1 | Corrigido | Ativação de tenant | Removidos `http://` e o fallback `APP_DOMAIN`/`localhost`; o URL usa o domínio registado do tenant. |
| P2 | Corrigido | Tutela e notificações de professor/planificação | Os links são construídos pelo gerador comum em vez de dependerem do host corrente ou de fallback a `url()`. |
| P2 | Corrigido | Criação de utilizador | Eliminado o envio duplicado da mesma notificação; o URL de login segue a regra tenant. |
| — | Sem CTA no email; não é atualmente um link visível | Notificações de prazos do diretor | As classes ainda passam URLs baseados em `url()`, mas os templates não usam `$url` num `href`. Não se identificou um link clicável para corrigir nesses emails. |
| — | Sem CTA no email | Sugestão de tema PAP (`SugestaoTemaPapNotification`) e remoção de aluno de grupo PAP | As classes calculam/passam `url`, mas os templates efetivamente usados não o apresentam num `href`. Existe outro template de sugestão (`sugestao-tema-pap-sge.blade.php`) com botão, mas esta notificação usa `sugestao-tema.blade.php`; não se alterou o comportamento visual sem confirmação de produto. |
| — | Não classificado como link tenant | Imagens/logos e emails centrais | URLs de `Storage` para imagens e links centrais/públicos não devem ser forçados ao domínio tenant. Foram preservados. |

O template `google-account-notification.blade.php` contém fallback `url('/')`, mas não foi encontrado um emissor ativo correspondente nesta revisão. Não foi tratado como fluxo comprovado.

## Reproduções antes da correção

No ambiente local, com `APP_URL=http://sge.localhost` e sem tenant ativo, a geração antiga das rotas tenant produzia links como `http://sge.localhost/dashboard/...`, mesmo quando o destinatário pertencia a um tenant. A reposição de password e o perfil incompleto também podiam produzir `http:///...` quando não havia domínio. Estes resultados confirmaram o defeito no código; não representam uma consulta a emails reais nem à configuração de produção.

## Validação executada

- O teste de [TenantNotificationUrlTest](../tests/Unit/Notifications/Concerns/TenantNotificationUrlTest.php) passou: 3 testes e 16 assertions, incluindo domínio explícito, esquema HTTPS, erro sem domínio e URLs completos para as 11 rotas tenant usadas nos CTAs.
- A revisão dos templates confirmou que os CTAs das restantes notificações que usam o helper ligam a variável URL fornecida pela notificação ao `href`; duas notificações não têm CTA e estão assinaladas acima.
- [TemaSubmetidoAoTutorNotificationTest](../tests/Unit/Notifications/Pap/TemaSubmetidoAoTutorNotificationTest.php) passou.
- Os testes de [SugestaoTemaPapNotificationTest](../tests/Unit/Notifications/CursoTutelado/SugestaoTemaPapNotificationTest.php) tiveram 4 aprovações e 2 erros por invocarem `enviarNotificacaoSugestaoTema()` e `adicionarCriadorAosDestinatarios()`, métodos inexistentes no trait atual. Esses erros não exercitam a geração de URLs.
- [CrossTenantGrupoPapVisibilityTest](../tests/Feature/CrossTenantGrupoPapVisibilityTest.php) não chegou às assertions nesta execução: o PHP atual não tem `pdo_sqlite`. Numa tentativa anterior com o driver carregado temporariamente, a migração `2026_09_11_154026_add_instituicao_id_to_prazos_provas_table.php` falhou por usar sintaxe `UPDATE ... INNER JOIN` específica de MySQL, incompatível com SQLite.
- `php artisan route:list --name=tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show` confirmou a rota cross-tenant.
- Pint passou e `php -l` passou em todos os ficheiros PHP alterados, incluindo o helper e os testes.

## Limites e operação

Esta revisão foi estática e baseada nos testes locais. Não foram consultados emails reais, logs de produção nem a configuração de produção. O comportamento em produção depende de `APP_URL` conter o esquema canónico (por exemplo, HTTPS) e de cada tenant destinatário ter um domínio registado.

Como a ausência de domínio agora gera erro em vez de um link enganador, é importante monitorizar falhas de envio em filas legadas sem contexto tenant/domínio. O bootstrapper de tenancy da fila está configurado, mas o teste de integração cross-tenant ainda deve ser executado numa base MySQL para confirmar o host e o caminho ponta a ponta.
