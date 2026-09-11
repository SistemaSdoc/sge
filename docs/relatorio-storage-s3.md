# Relatório de armazenamento de ficheiros e compatibilidade com S3

**Data da análise:** 2026-09-11
**Aplicação:** SGE, Laravel 13.31.0, Laravel Boost 2.7.1
**Escopo:** salvamento, leitura, download, anexos, eliminação, URLs e isolamento multi-tenant.

## Actualização após a migração de código

Em 2026-09-11, os fluxos de aplicação foram migrados para discos S3 scoped:

- `public` usa S3 com o prefixo `public` e `private` usa S3 com o prefixo `private`.
- Uploads públicos e privados deixaram de depender dos roots locais.
- Recibos, anexos de email, logos em PDFs e downloads deixaram de usar `path()`/`public_path('storage/...')`.
- A tenancy deixou de reescrever os discos S3; apenas o disco local de suporte continua tenant-aware.
- O adaptador S3 e o adaptador de prefixação scoped estão declarados directamente no Composer.
- O teste do serviço de recibos valida também a leitura do conteúdo pelo disco.

Continua a ser necessária a migração operacional dos ficheiros que já existem em `storage/app/public` e `storage/app/private` para os prefixos correspondentes no bucket. Essa operação deve ser executada com backup e validação, não automaticamente durante o deploy.

## Resumo executivo

A ligação do adaptador S3 está disponível, e o `.env` define `FILESYSTEM_DISK=s3`, mas a aplicação ainda não está globalmente compatível com S3.

O motivo principal é que os fluxos existentes usam uma mistura de discos explícitos locais (`public` e `private`) e do disco default. Assim, definir `FILESYSTEM_DISK=s3` só altera chamadas que não especificam disco. Os uploads atuais mais importantes continuam locais, enquanto alguns leitores dependem de caminhos físicos, `public_path()` ou `Storage::url()` sem disco explícito.

**Conclusão:** não é recomendável activar a aplicação em produção assumindo que todos os ficheiros já estão no S3. Os fluxos públicos podem continuar a funcionar localmente, mas os fluxos privados de recibos e PAP têm incompatibilidades concretas com S3.

## Fontes e método

- Inspecção local de `config/filesystems.php`, `config/tenancy.php`, controllers, services, actions, resources, notifications e rotas.
- Laravel Boost instalado (`laravel/boost` 2.7.1); foi executado `php artisan boost:list-skills` para confirmar as skills disponíveis, incluindo `laravel-best-practices`.
- Context7 consultado com a documentação Laravel 13 para filesystem, S3, uploads, visibilidade e URLs temporárias.
- Dependências confirmadas com Composer. O framework inclui o requisito de `league/flysystem-aws-s3-v3`, mas o pacote não aparece como dependência directa em `composer.json` na análise actual.

## Configuração encontrada

### Pontos conformes

- `config/filesystems.php` lê as credenciais por `env()` na camada de configuração, como recomendado.
- O disco `s3` está configurado com `key`, `secret`, `region`, `bucket`, `url`, `endpoint` e `use_path_style_endpoint`.
- As variáveis S3 necessárias estão configuradas no ambiente: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` e `AWS_USE_PATH_STYLE_ENDPOINT`.
- O adaptador Flysystem S3 e o adaptador de prefixação scoped estão declarados directamente no `composer.json`.
- O código usa `UploadedFile::store()` em vários uploads, aproveitando nomes gerados pelo Laravel em parte dos fluxos.
- Os fluxos PAP e recibos tentam separar ficheiros privados dos públicos por disco, uma distinção correcta em princípio.

### Não conformidades e riscos


| Prioridade | Área               | Evidência                                                                                                                            | Impacto                                                                                                                                              |
| ---------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Crítica   | Recibos             | `ReciboPdfService` usa `path()` e `file_get_contents()`; `ReciboController` usa `response()->file()`/`download()` com caminho físico | Falha quando o disco de recibos for S3, porque não existe caminho local utilizável                                                                 |
| Alta       | Anexos de email     | `PagamentoRegistadoNotification` usa `Storage::disk('private')->path()`                                                               | O email não conseguirá anexar recibos guardados remotamente                                                                                        |
| Alta       | Escolha de disco    | Uploads usam explicitamente`public` e `private`, ambos discos `local`                                                                 | `FILESYSTEM_DISK=s3` não migra esses uploads para S3                                                                                                |
| Alta       | Tenancy             | `config/tenancy.php` lista apenas `local`, `public` e `private`                                                                       | O S3 não recebe prefixo/isolamento tenant pelo bootstrapper; pode haver colisão ou mistura entre tenants                                           |
| Alta       | URLs públicas      | `GrupoPap\ShowResource` usa `Storage::url()` sem disco explícito                                                                     | A URL pode ser gerada pelo default S3 para um ficheiro que foi gravado no disco local`public`, ou vice-versa                                         |
| Alta       | Rota pública       | `routes/tenant.php` serve sempre `Storage::disk('public')`                                                                            | A rota não serve objectos S3 e não aproveita URLs do bucket                                                                                        |
| Média     | Caminhos locais     | `ReciboPdfService`, views PDF e controllers assumem `storage_path()`/`public_path('storage/...')`                                     | Logos, templates e recibos ficam dependentes do filesystem local                                                                                     |
| Média     | Tratamento de erros | Disco S3 está com`throw => false` e `report => false`                                                                                | Falhas de escrita podem virar`false` ou passar despercebidas, dificultando diagnóstico e consistência                                              |
| Média     | Nomes de ficheiro   | `TrabalhoPapService::submeter()` usa `getClientOriginalName()` em `storeAs()`                                                         | Nomes com caracteres inesperados, colisões e informação do utilizador entram no object key; o nome original deve ser mantido apenas como metadado |
| Alta       | Segredos            | O`.env` contém credenciais S3 preenchidas                                                                                            | Se esse ficheiro tiver sido partilhado, versionado ou exposto, as chaves devem ser revogadas/rotacionadas imediatamente                              |

## Inventário dos fluxos

### Uploads públicos

- `UploadCursoTuteladoDocumentos` grava em `public` e remove substituídos no mesmo disco.
- `InstituicaoController` grava logos em `public`.
- `CursoTuteladoResourceShow` e a rota `/storage/{path}` assumem o modelo de storage local.

Estes fluxos são coerentes apenas se `public` continuar a ser um disco local. Para S3, devem gravar explicitamente no disco S3 com visibilidade pública, ou passar a emitir URLs do disco correcto.

### Uploads privados

- `TrabalhoPapService` grava versões PAP em `private` com `storeAs()`.
- `TrabalhoPapController` e `Colegios\TrabalhoPapController` fazem leituras/downloads pelo disco privado.
- `ReciboPdfService` grava recibos em `private` e move um ficheiro temporário para o caminho final.

Os dois primeiros grupos podem ser adaptados para S3 usando operações de filesystem (`get`, `readStream`, `download` ou `temporaryUrl`) em vez de caminhos físicos. O serviço de recibos e o anexo de email exigem alteração antes da migração.

### Geração e leitura local

- `ReciboPdfService::caminhoAbsoluto()` depende de `FilesystemAdapter::path()`.
- `ReciboController` responde a partir de um ficheiro local.
- `PagamentoRegistadoNotification` anexa a partir de um caminho local.
- Views e serviços de PDF usam `public_path()` ou `storage_path()` para logos e templates.

Esses usos não são portáveis para S3. Templates que são parte do código/deploy podem permanecer locais; ficheiros enviados por utilizadores e logos não devem depender de `public_path()`.

## Conformidade com a documentação Laravel 13


| Recomendação documentada                                    | Estado                    | Observação                                                                                         |
| ------------------------------------------------------------- | ------------------------- | ---------------------------------------------------------------------------------------------------- |
| Configurar S3 na configuração e manter segredos no ambiente | Parcialmente conforme     | A configuração existe, mas as credenciais preenchidas no`.env` exigem cuidado operacional          |
| Especificar o disco quando há múltiplos discos              | Não conforme             | Há chamadas sem disco e chamadas fixadas em discos locais que não correspondem ao S3 pretendido    |
| Usar`store()`/`putFile()` para uploads                        | Parcialmente conforme     | Há uso correcto de`store()`, mas PAP usa `storeAs()` com nome original não normalizado             |
| Usar`public` apenas para ficheiros públicos                  | Conforme em intenção    | A separação existe, mas ambos os discos são locais e não há estratégia S3 equivalente definida |
| Usar URLs temporárias para ficheiros privados                | Não conforme             | Downloads privados são feitos por caminhos físicos ou respostas locais                             |
| Evitar`path()` para storage remoto                            | Não conforme             | Recibos e anexos dependem de`path()`                                                                 |
| Fazer upload/download por stream quando apropriado            | Não conforme nos recibos | O fluxo usa conteúdo inteiro e APIs de ficheiro local                                               |
| Fazer falhas de escrita lançarem excepções                 | Risco                     | O disco S3 usa`throw=false`; deve ser uma decisão explícita e coberta por testes                   |
| Isolar dados por tenant                                       | Não conforme para S3     | O bootstrapper não inclui o disco S3 nem há prefixo tenant explícito nos object keys              |

## Plano de correcção recomendado

### 1. Decidir a topologia de discos

Escolher explicitamente uma destas estratégias:

- **Migração total:** `public` e `private` passam a ser discos S3 separados, ou todos os fluxos passam a chamar `disk('s3')`/um disco nomeado equivalente.
- **Estratégia híbrida:** assets/templates permanecem locais, mas uploads de utilizadores usam discos S3 nomeados, por exemplo `s3-public` e `s3-private`.

Não é aconselhável reutilizar os nomes `public` e `private` para significados diferentes sem uma migração completa, pois isso torna o comportamento dependente da configuração carregada.

### 2. Corrigir os leitores remotos

- Recibos: trocar `path()`/`response()->file()` por uma resposta baseada no conteúdo/stream do disco, ou gerar uma URL temporária quando o caso de uso permitir.
- Anexos: usar conteúdo ou stream suportado pelo mailer, sem exigir um caminho local.
- PAP privado: usar `download()`/`get()`/`readStream()` do disco e URLs temporárias para visualização controlada.
- Logos públicos: gerar URL a partir do disco onde o logo foi efectivamente gravado; eliminar `public_path('storage/...')` para objectos S3.

### 3. Corrigir isolamento multi-tenant

Definir uma convenção de object key com o tenant, por exemplo `tenants/{tenant-id}/...`, ou configurar o mecanismo de tenancy para o disco S3 conforme a estratégia escolhida. Validar que um tenant não consegue ler, substituir ou apagar objectos de outro tenant.

### 4. Melhorar robustez e segurança

- Activar `throw => true` no disco de escrita S3, tratar exceções e registar contexto sem segredos.
- Validar MIME real, extensão esperada e tamanho antes de gravar uploads.
- Não usar o nome original como nome físico; guardar `getClientOriginalName()` apenas como metadado apresentado ao utilizador.
- Rodar as credenciais S3 se o `.env` tiver sido exposto e garantir que `.env` não é versionado.
- Confirmar permissões IAM mínimas: leitura, escrita e remoção apenas no bucket/prefixo necessários.

### 5. Cobertura mínima antes da activação

Adicionar testes com `Storage::fake()` para cada fluxo relevante e verificar:

- upload público e URL gerada no disco esperado;
- upload privado sem exposição pública;
- substituição e remoção do ficheiro antigo;
- download/visualização de recibo e PAP;
- anexo de recibo no email;
- isolamento dos caminhos por tenant;
- falha de escrita propagada como erro tratável.

## Veredicto

**Estado actual: código preparado para usar S3; dados antigos ainda requerem migração operacional.**

Os fluxos de aplicação já gravam e consultam ficheiros através dos discos S3 `public` e `private`, sem depender de caminhos físicos locais. Antes de produção, é necessário copiar os ficheiros existentes para os prefixos S3, confirmar permissões IAM e validar o acesso de cada tenant.
