RUPE — Integração e fluxo de pagamento

Resumo

Este documento descreve o comportamento implementado para o RUPE (referência de pagamento) e como o fluxo de solicitação de documentos interage com o pagamento. O código actual inclui um gerador RUPE "manual" (stub) e notificações via base de dados.

Fluxo principal

1. Aluno cria um pedido de documento (SolicitacaoDocumento).
   - No controller de criação (`SolicitacaoDocumentoController@store`) o gerador RUPE é invocado através da interface `App\Services\Rupe\RupeGeneratorInterface`.
   - A implementação por defeito é `App\Services\Rupe\RupeGeneratorManual` e escreve os campos rupe_referencia, rupe_entidade, rupe_valor e rupe_gerado_em na tabela `solicitacoes_documentos`.
   - O aluno recebe uma notificação do tipo `rupe_disponivel` guardada na tabela `notifications`.

2. Aluno paga o RUPE externamente e leva o comprovativo à secretaria.
   - A secretaria (ou utilizador autorizado) clica em "Documento Pago" na interface da secretaria/emissão.
   - O sistema marca `estado_pagamento = 'pago'` e envia notificação `pagamento_confirmado` ao aluno.

3. Emissão do documento segue o processo existente (aprovado → emitido).

Armazenamento

- Tabela: `solicitacoes_documentos`
  - rupe_referencia (string)
  - rupe_entidade (string)
  - rupe_valor (decimal)
  - rupe_gerado_em (timestamp)
  - estado_pagamento (enum: pendente|pago)

Interface do Gerador RUPE

- Interface: `App\Services\Rupe\RupeGeneratorInterface`
- Implementação de desenvolvimento: `App\Services\Rupe\RupeGeneratorManual`
- Quando a API govermental estiver pronta, criar `App\Services\Rupe\RupeGeneratorGoverno` que implemente a interface e fazer o bind no `AppServiceProvider`

Variáveis de ambiente (sugestão)

- RUPE_API_URL: URL base para a API do RUPE (quando existir)
- RUPE_API_KEY: Chave/credential para a API (opcional)
- RUPE_PROVIDER: 'manual' (default) ou 'governo' — se preferires controlar por env
- QUEUE_CONNECTION: 'sync' | 'database' | 'redis' — dependente do envio de notificações/queues em background

Autorização

- A acção de marcar um pedido como pago é protegida por Policy `App\Policies\SolicitacaoDocumentoPolicy`.
  - Permite se o user pertence à mesma `instituicao_emissora_id` ou tem um role `Secretaria` / `Emissao`.
  - SuperAdmin continua a ter acesso via Gate::before.

Pontos a considerar para produção

- Trocar o gerador manual pelo implementador real após validar a API do governo.
- Possível envio de notificações em real-time via broadcast (configurar canais e Redis/Pusher) para melhorar UX.
- Backfill: definir script para marcar `estado_pagamento = 'pendente'` para pedidos existentes e/ou importar referências quando houver dados externos.

Notas de desenvolvimento

- Para testar localmente sem a API do governo, o gerador manual é suficiente e preenche os campos do RUPE.
- O frontend do aluno consome `classes_disponiveis` e `pode_certificado` do controller Inertia para condicionar opções no formulário de solicitação.

