# Refactor de Recibos

## Diagnostico

`Pagamento` esta a concentrar responsabilidades que pertencem a camadas diferentes:

- numeracao do recibo;
- carregamento da arvore de dados do PDF;
- renderizacao com Dompdf;
- lock e transacao;
- criacao de diretorios e escrita no filesystem;
- validacao do artefato gerado.

`ReciboController` tambem conhece detalhes do filesystem e da validade binaria do PDF. A notification chama a geracao como efeito colateral de `toMail()`. Alem de dificultar testes, isso deixa a geracao pesada dentro de requests e pode produzir comportamentos diferentes entre HTTP, fila e tenancy.

## Arquitetura proposta

### 1. Manter o model focado em Eloquent

`Pagamento` deve conter tabela, casts, fillable, relacionamentos e pequenos predicados de estado. Remover `gerarRecibo()` do model.

### 2. Criar uma Action de caso de uso

Criar `App\Actions\Tenant\Pagamento\GerarRecibo` com `handle(Pagamento $pagamento, bool $forcar = false): ReciboResult`.

A Action deve coordenar:

- lock por pagamento;
- alocacao idempotente de `numero_recibo`;
- transacao curta apenas para a numeracao e persistencia do estado;
- chamada do renderer;
- escrita atomica no disco tenant-aware;
- atualizacao de `recibo_path` somente depois do arquivo valido existir.

O Dompdf e o filesystem nao devem executar dentro da transacao de banco.

### 3. Isolar renderizacao e armazenamento

Criar um servico pequeno, por exemplo `ReciboPdfService`, responsavel por:

- receber os dados necessarios;
- renderizar a view `pdf.recibo`;
- garantir assinatura PDF;
- salvar via disco `private` configurado pelo tenancy;
- substituir o arquivo final apenas apos a escrita temporaria concluir.

Nao criar repository para isto: o limite relevante e o adaptador de PDF/filesystem, nao uma nova camada de persistencia Eloquent.

### 4. Tornar o controller HTTP-only

`ReciboController` deve apenas:

- autorizar o pagamento;
- pedir um recibo pronto a um servico/reader;
- devolver `response()->file()` para visualizar ou `response()->download()` para exportar.

A validacao de PDF, lock, criacao de pasta e regeneracao devem sair do controller.

### 5. Remover geracao da Notification

`PagamentoRegistadoNotification::toMail()` nao deve gerar recibo. A geracao deve acontecer no caso de uso que registra o pagamento ou em um Job disparado apos o commit. A notification apenas anexa o arquivo se ele estiver pronto.

## Segunda etapa: fila

Depois da separacao, criar `GerarReciboJob` com `ShouldQueue`, `ShouldBeUnique` por pagamento, retries e `failed()` com contexto. O endpoint deve:

- servir imediatamente se o arquivo estiver pronto;
- aguardar apenas uma geracao sincronizada controlada durante a transicao;
- evoluir para resposta de processamento pendente quando a UI suportar esse estado.

O driver de cache do lock deve ser compartilhado e suportar locks. No tenancy atual, nao usar diretamente o repository de cache tenantizado por tags; manter o store de lock explicitamente configurado.

## Banco e consistencia

- Adicionar indice unico tenant-scoped para `instituicao_id` e `numero_recibo`.
- Manter a numeracao idempotente quando `numero_recibo` ja existir.
- Tratar colisao de numeracao com retry controlado.
- Nao manter Dompdf ou IO de arquivo dentro de `DB::transaction()`.

## Testes necessarios

- Action gera um PDF valido e persiste `recibo_path`.
- Regeneracao preserva o mesmo numero do recibo.
- Arquivo temporario incompleto nao substitui o PDF final.
- Duas execucoes do mesmo pagamento sao serializadas.
- Visualizacao devolve `inline` e `application/pdf`.
- Exportacao devolve `attachment`, nome ASCII e `application/pdf`.
- Notification nao dispara geracao.
- Pagamento de outro tenant nao pode ser lido nem anexado.
- Falha do renderer ou storage nao atualiza `recibo_path`.

## Ordem de implementacao

1. Criar contratos/resultados e mover a logica para Action e servico, mantendo chamadas sincronas.
2. Migrar controller e notification para as novas fronteiras.
3. Adicionar indice unico e testes de concorrencia/tenant.
4. Validar com Pint, testes focados e um teste HTTP autenticado por tenant.
5. Introduzir Job apos a UI tratar recibo pendente e monitorar falhas.