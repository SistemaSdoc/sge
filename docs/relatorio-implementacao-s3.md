# Relatório técnico: implementação S3 no SGE

**Data:** 2026-09-11  
**Aplicação:** SGE  
**Framework:** Laravel 13.31.0  
**Multi-tenancy:** stancl/tenancy 3.10.1  
**Objectivo:** documentar tudo o que foi implementado para guardar, consultar, visualizar, descarregar e apagar ficheiros através de S3.

## 1. Resumo executivo

O código da aplicação foi adaptado para usar S3 como storage dos ficheiros enviados pelos utilizadores e dos documentos consultados pela aplicação.

A solução usa um único bucket S3 com dois discos Laravel scoped:

```text
public  -> s3/public/...
private -> s3/private/...
```

Os nomes `public` e `private` continuam a ser usados pelo código para preservar a API existente e os caminhos relativos guardados na base de dados. Internamente, ambos deixaram de ser discos locais e passaram a ser prefixos sobre o disco S3 base.

### Estado actual

| Área | Estado |
| --- | --- |
| Adapter Flysystem S3 | Implementado e declarado directamente |
| Disco S3 base | Implementado |
| Disco público S3 | Implementado como scoped |
| Disco privado S3 | Implementado como scoped |
| Uploads de logos | Migrados para S3 |
| Uploads de documentos de cursos | Migrados para S3 |
| Uploads de trabalhos PAP | Migrados para S3 |
| Uploads de correcções PAP | Migrados para S3 |
| Geração e leitura de recibos | Adaptadas para S3 |
| Downloads e visualizações PAP | Adaptados para storage remoto |
| Anexos de email | Adaptados para conteúdo remoto |
| Logos em PDFs | Adaptados para conteúdo remoto/base64 |
| Isolamento de prefixo `public`/`private` | Implementado |
| Prefixo individual por tenant | Não implementado como prefixo S3 separado |
| Migração dos ficheiros antigos locais | Ainda não executada |
| Teste focado de recibos | Passa |
| Suite completa | Não passa por problemas de ambiente/testes preexistentes |

## 2. Dependências instaladas

Foram adicionadas ao `composer.json`:

```json
"league/flysystem-aws-s3-v3": "^3.25",
"league/flysystem-path-prefixing": "^3.0"
```

### Função de cada dependência

- `league/flysystem-aws-s3-v3`: adapter Flysystem para Amazon S3 e serviços compatíveis com S3.
- `league/flysystem-path-prefixing`: suporte ao driver Laravel `scoped`, usado para aplicar automaticamente os prefixos `public` e `private`.

O `composer.lock` foi actualizado. O comando `composer audit --no-interaction` não encontrou advisories de segurança.

## 3. Configuração de filesystem

Ficheiro: [config/filesystems.php](../config/filesystems.php)

### 3.1 Disco `s3`

O disco S3 base usa:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => true,
],
```

### 3.2 Disco `public`

```php
'public' => [
    'driver' => 'scoped',
    'disk' => 's3',
    'prefix' => env('AWS_PUBLIC_PREFIX', 'public'),
    'visibility' => 'public',
    'throw' => true,
],
```

Todos os caminhos usados através de `Storage::disk('public')` ficam fisicamente sob:

```text
{AWS_PUBLIC_PREFIX}/...
```

Por defeito:

```text
public/...
```

Este disco é destinado a logos e documentos que podem ser apresentados por URL pública.

### 3.3 Disco `private`

```php
'private' => [
    'driver' => 'scoped',
    'disk' => 's3',
    'prefix' => env('AWS_PRIVATE_PREFIX', 'private'),
    'visibility' => 'private',
    'throw' => true,
],
```

Todos os caminhos usados através de `Storage::disk('private')` ficam fisicamente sob:

```text
{AWS_PRIVATE_PREFIX}/...
```

Por defeito:

```text
private/...
```

Este disco é destinado a trabalhos PAP, correcções e recibos.

### 3.4 Tratamento de erros

O disco S3 e os discos scoped usam `throw => true`. Falhas de escrita, movimentação ou remoção devem gerar excepções em vez de serem silenciosamente convertidas em `false`.

Isto é importante para impedir que a base de dados guarde um caminho de ficheiro quando o objecto não foi realmente criado no bucket.

### 3.5 Symlink local

O symlink seguinte continua declarado:

```php
public_path('storage') => storage_path('app/public')
```

Ele é legado da configuração local e já não é usado pelos uploads S3. Pode continuar a existir para compatibilidade com assets locais, mas não serve para consultar objectos guardados no S3.

## 4. Variáveis de ambiente

Ficheiro de referência: [.env.example](../.env.example)

Variáveis suportadas:

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_PUBLIC_PREFIX=public
AWS_PRIVATE_PREFIX=private
```

### Significado

- `FILESYSTEM_DISK=s3`: define S3 como disco default para chamadas sem disco explícito.
- `AWS_ACCESS_KEY_ID`: chave de acesso.
- `AWS_SECRET_ACCESS_KEY`: segredo da chave.
- `AWS_DEFAULT_REGION`: região do bucket.
- `AWS_BUCKET`: bucket usado pelo adapter.
- `AWS_URL`: URL base opcional para geração de URLs.
- `AWS_ENDPOINT`: endpoint opcional para S3 compatível, como R2, MinIO ou Spaces.
- `AWS_USE_PATH_STYLE_ENDPOINT`: necessário para alguns serviços compatíveis com S3.
- `AWS_PUBLIC_PREFIX`: prefixo físico do disco público.
- `AWS_PRIVATE_PREFIX`: prefixo físico do disco privado.

### Segurança operacional

As credenciais não devem ser colocadas no relatório, commitadas no repositório ou enviadas para outro agente. Se as chaves reais foram partilhadas, expostas ou versionadas, devem ser imediatamente rotacionadas.

## 5. Alteração da tenancy

Ficheiro: [config/tenancy.php](../config/tenancy.php)

O `FilesystemTenancyBootstrapper` agora lista apenas o disco local:

```php
'disks' => [
    'local',
],
```

E apenas o disco local recebe `root_override`:

```php
'root_override' => [
    'local' => '%storage_path%/app/',
],
```

### Motivo

O bootstrapper da tenancy altera a propriedade `root` dos discos listados. Isso faz sentido para discos locais, mas não para os discos S3 scoped, que não têm uma raiz local.

Se `public` e `private` continuassem nessa lista, o bootstrapper poderia tentar aplicar uma raiz baseada em `storage_path()` a discos que agora são remotos.

### Limitação importante

Os discos `public` e `private` têm prefixos globais `public` e `private`, não um prefixo adicional automático por tenant. Portanto, o isolamento actual depende de:

- autorização Laravel antes de cada leitura/escrita;
- caminhos de negócio que incluem IDs de entidades, por exemplo `cursos-tutelados/{id}` e `trabalhos_pap/{grupo_id}`;
- separação pública/privada do objecto.

Para isolamento físico mais forte, a próxima etapa deve usar prefixos como:

```text
tenants/{tenant_id}/public/...
tenants/{tenant_id}/private/...
```

ou discos scoped específicos por tenant criados durante a inicialização da tenancy.

## 6. Uploads migrados

### 6.1 Logos de instituições

Ficheiro: [InstituicaoController.php](../app/Http/Controllers/Tenant/InstituicaoController.php)

Criação:

```php
$dados['logo'] = $request->file('logo')->store('logos', 'public');
```

Actualização:

1. Remove o logo anterior através de `Storage::disk('public')->delete()`.
2. Guarda o novo logo no mesmo disco público S3.

Eliminação:

- Ao apagar a instituição, o logo é removido do disco público S3.

Object key aproximado:

```text
public/logos/{nome-gerado-pelo-laravel}
```

O nome é gerado pelo Laravel através de `store()`, evitando usar directamente o nome original enviado pelo utilizador.

### 6.2 Documentos de cursos tutelados

Ficheiro: [UploadCursoTuteladoDocumentos.php](../app/Actions/Tenant/CursoTutelado/UploadCursoTuteladoDocumentos.php)

Tipos tratados:

- `criterios_pap`
- `manual_pt`
- `estrutura_trabalho_pap`

Cada documento é guardado com:

```text
cursos-tutelados/{curso_tutelado_id}/{tipo}
```

No bucket, com o prefixo scoped:

```text
public/cursos-tutelados/{curso_tutelado_id}/{tipo}/{nome-gerado}
```

O caminho relativo é guardado na coluna correspondente do modelo. Na substituição:

1. O novo objecto é gravado no S3.
2. A base de dados é actualizada.
3. O objecto anterior é removido.

Se a operação falhar, os novos objectos já gravados são eliminados no bloco de excepção.

### 6.3 Trabalhos PAP

Ficheiro: [TrabalhoPapService.php](../app/Services/Tenant/TrabalhoPapService.php)

Submissões de alunos:

```php
$ficheiro->store(
    "trabalhos_pap/{$trabalho->grupo_pap_id}",
    'private'
);
```

Object key aproximado:

```text
private/trabalhos_pap/{grupo_pap_id}/{nome-hash}
```

O nome físico é gerado pelo Laravel. O nome original continua guardado separadamente em `nome_original` para ser apresentado ao utilizador.

Correcções:

```text
private/trabalhos_pap/{grupo_pap_id}/correcoes/{tipo}-v{numero-versao}/{nome-hash}
```

Os tipos de correcção incluem tutor, coordenação ou correcção genérica.

### 6.4 Recibos PDF

Ficheiro: [ReciboPdfService.php](../app/Services/Tenant/Recibos/ReciboPdfService.php)

Os recibos são gravados no disco privado:

```text
private/recibos/{instituicao_id}/{numero_recibo}.pdf
```

O fluxo usa um objecto temporário com UUID:

```text
recibos/{instituicao_id}/{numero_recibo}.pdf.tmp-{uuid}
```

Fluxo:

1. Gera o PDF em memória através do DomPDF.
2. Valida a assinatura `%PDF-`.
3. Grava o conteúdo no objecto temporário S3.
4. Move o objecto temporário para o caminho final.
5. Remove o temporário no bloco `finally`, quando existir.

Não é usado `makeDirectory()`, porque S3 não tem directórios físicos.

## 7. Leitura, visualização e downloads

### 7.1 Documentos públicos

Os resources usam explicitamente o disco público:

- [CursoTuteladoResourceShow.php](../app/Http/Resources/Tenant/CursoTutelado/CursoTuteladoResourceShow.php)
- [ShowResource.php](../app/Http/Resources/Tenant/GrupoPap/ShowResource.php)

As URLs são geradas por `FilesystemAdapter::url()` através de helpers locais com anotação de tipo.

Isto evita o erro de escolher implicitamente o disco default ou gerar uma URL baseada em `public/storage` local.

### 7.2 Rota `/storage/{path}`

Ficheiro: [routes/tenant.php](../routes/tenant.php)

A rota:

1. Resolve explicitamente `Storage::disk('public')`.
2. Confirma que o objecto existe.
3. Devolve `response($path)` do `FilesystemAdapter`.

Ela continua a ser uma rota da aplicação, mas consulta o objecto no S3 através do adapter. Não depende de symlink local nem de `public_path()`.

### 7.3 Trabalhos PAP privados

Controllers envolvidos:

- [TrabalhoPapController.php](../app/Http/Controllers/Tenant/TrabalhoPapController.php)
- `app/Http/Controllers/Tenant/Colegios/TrabalhoPapController.php`

Visualização:

- Confirma `exists()` no disco privado.
- Lê com `get()`.
- Devolve o conteúdo com `Content-Type: application/pdf`.
- Usa `Content-Disposition: inline`.

Download:

- Confirma `exists()`.
- Usa `Storage::disk('private')->download()`.
- Mantém o nome original como nome de download, sem o usar como object key.

Correcções PAP seguem o mesmo padrão.

### 7.4 Recibos

Ficheiro: [ReciboController.php](../app/Http/Controllers/Tenant/ReciboController.php)

O controller já não usa:

- `Storage::path()`;
- `response()->file()`;
- `response()->download()` com caminho físico.

Agora:

- chama `ReciboPdfService::existe()`;
- lê com `ReciboPdfService::conteudo()`;
- devolve uma resposta HTTP com o conteúdo do objecto S3;
- usa `inline` para visualização;
- usa `attachment` para exportação.

## 8. Notificações e emails

### 8.1 Anexo de recibo

Ficheiro: [PagamentoRegistadoNotification.php](../app/Notifications/Aluno/PagamentoRegistadoNotification.php)

Antes, o fluxo dependia de:

```php
Storage::disk('private')->path(...)
```

Isso exigia um ficheiro local. Agora:

1. Resolve o disco privado.
2. Confirma `exists()`.
3. Lê com `get()`.
4. Usa `MailMessage::attachData()`.

O email já não exige que o recibo exista fisicamente no servidor da aplicação.

### 8.2 Logo no email de aluno criado

Ficheiro: [AlunoCriadoNotification.php](../app/Notifications/Aluno/AlunoCriadoNotification.php)

O logo é transformado em URL através do disco público S3. O adapter é anotado como `FilesystemAdapter` para permitir a chamada `url()` no analisador estático.

### 8.3 Logo no email de propina em atraso

Ficheiro: [PropinaEmAtrasoNotification.php](../app/Notifications/PropinaEmAtrasoNotification.php)

O logo também é resolvido no disco público S3 através de `FilesystemAdapter::url()`.

Este ficheiro ainda apresenta erros independentes de storage no analisador:

- view `emails.propina-em-atraso` não encontrada;
- rotas `pagamentos.create` e `pagamentos.index` não encontradas.

Esses problemas não fazem parte da migração S3.

## 9. Logos em PDFs e relatórios

### 9.1 Recibo PDF

Ficheiros:

- [ReciboPdfService.php](../app/Services/Tenant/Recibos/ReciboPdfService.php)
- [recibo.blade.php](../resources/views/pdf/recibo.blade.php)

O serviço:

1. Consulta o logo através do disco público S3.
2. Lê o conteúdo com `get()`.
3. Detecta extensões suportadas.
4. Converte o conteúdo para uma data URI base64.
5. Passa `logoBase64` para a view DomPDF.

A view usa a data URI em vez de:

```php
public_path('storage/'.$logo)
```

### 9.2 Relatório de propinas

Ficheiro: [RelatorioPropinaController.php](../app/Http/Controllers/Tenant/RelatorioPropinaController.php)

O logo é lido do disco público S3 e convertido para base64 antes de ser enviado para o template PDF.

Extensões tratadas:

- JPG/JPEG;
- PNG;
- GIF;
- WebP.

## 10. Ficheiros que continuam locais por desenho

A migração não pretende mover todos os ficheiros do servidor indiscriminadamente. Os seguintes continuam locais porque são infra-estrutura da aplicação ou assets de deploy:

### Templates internos

- `storage/app/templates/Declaracao_com_notas_template.docx`
- `storage/app/templates/Declaracao_template.docx`

Esses templates são lidos por `storage_path()` e fazem parte do runtime da aplicação, não são uploads de utilizadores.

### Assets estáticos

Existem assets como `Emblem_of_Angola.svg.png` usados por Browsershot/PDF através de `public_path()`. São ficheiros distribuídos com a aplicação, não documentos armazenados no bucket.

### Infra-estrutura local

Também continuam locais por desenho:

- logs;
- cache de ficheiros;
- sessões, quando configuradas para filesystem;
- ficheiros temporários de Browsershot/Puppeteer;
- outros dados internos do framework.

## 11. O que foi removido ou evitado

Os fluxos migrados deixaram de depender de:

- `Storage::path()` para ficheiros S3;
- `file_get_contents()` sobre paths locais de uploads;
- `public_path('storage/...')` para logos enviados;
- `response()->file()` com caminho local de recibos;
- `response()->download()` com caminho físico de recibos;
- `Storage::url()` sem disco explícito;
- `makeDirectory()` para criar directórios S3;
- nomes físicos baseados directamente em `getClientOriginalName()` nos uploads PAP novos.

## 12. Testes e validações executadas

### Validações que passaram

- `php -l` nos ficheiros PHP alterados.
- `vendor/bin/pint --dirty --format agent`.
- `composer validate --no-check-publish`.
- `composer audit --no-interaction`.
- `php artisan config:cache`.
- `php artisan route:list --path=storage --except-vendor`.
- Instanciação dos adapters `public` e `private`, confirmados como `League\Flysystem\AwsS3V3\AwsS3V3Adapter`.
- Teste focado `tests/Feature/ReciboPdfServiceTest.php`.

Resultado do teste focado:

```text
1 passed, 4 assertions
```

O teste valida:

- reconhecimento de PDF por assinatura;
- rejeição de conteúdo inválido;
- rejeição de caminho nulo;
- leitura do conteúdo através do storage.

### Suite completa

A suite completa não ficou verde por motivos externos à alteração S3. Foram observados:

- tabelas ausentes em `central_database`;
- testes a usar a ligação MySQL sem base/migrations de teste preparadas;
- testes antigos a referenciar `App\Models\User`, enquanto o projecto usa modelos tenant;
- teste de notificação sem tenant activo.

Essas falhas devem ser avaliadas separadamente e não foram corrigidas como parte desta migração.

## 13. Dados antigos ainda não migrados

A alteração de código não copia automaticamente ficheiros que já existiam no servidor.

O inventário local encontrou ficheiros em roots como:

```text
storage/app/public
storage/app/private
storage/tenantimcl/app/public
storage/tenantcua/app/private
```

Também houve uma pasta com erro de permissão durante o inventário, portanto a contagem local não deve ser considerada um inventário completo.

### Procedimento ainda necessário

1. Fazer backup dos roots locais.
2. Identificar o tenant de cada root.
3. Mapear cada root local para o prefixo S3 correcto.
4. Copiar sem alterar os caminhos relativos usados na base de dados.
5. Validar `exists()` no disco S3 para cada caminho referenciado na base.
6. Testar visualização, download, email e PDFs.
7. Só depois remover ou arquivar os ficheiros locais.

Não executar uma limpeza automática antes da validação. Ficheiros sem referência na base de dados devem ser analisados antes de serem eliminados.

## 14. Riscos e pontos de avaliação para o próximo agente

### 14.1 Isolamento por tenant

O prefixo actual separa apenas público e privado. Não há ainda um prefixo físico obrigatório por tenant. Avaliar se o modelo de autorização é suficiente ou se deve ser implementado:

```text
tenants/{tenant_id}/public/...
tenants/{tenant_id}/private/...
```

### 14.2 Configuração pública do bucket

O disco `public` usa visibilidade pública e `url()`. Confirmar no bucket:

- política de acesso adequada;
- ausência de exposição do prefixo `private`;
- CORS, caso o frontend aceda directamente ao bucket;
- URL/endpoint correcto;
- ACLs e object ownership compatíveis com o provider.

### 14.3 IAM

A credencial usada pela aplicação deve ter apenas as permissões necessárias, preferencialmente limitadas aos prefixos do bucket:

- `GetObject`;
- `PutObject`;
- `DeleteObject`;
- listagem limitada quando necessária.

### 14.4 Performance de PDFs e emails

Os logos e recibos são carregados integralmente em memória com `get()`/base64. Para ficheiros pequenos, isso é aceitável. Para ficheiros maiores, avaliar `readStream()` e respostas streamadas.

### 14.5 Logs de upload PAP

`TrabalhoPapService` ainda regista `config('filesystems.disks.private.root')` e `is_writable(...)`, conceitos que pertenciam ao disco local. Com S3, `root` pode não existir e `is_writable()` não valida a capacidade de escrita no bucket. Esses campos de debug devem ser removidos ou substituídos por informação que não revele credenciais e que faça sentido para S3.

### 14.6 Rotas públicas através da aplicação

A rota `/storage/{path}` mantém o tráfego dos documentos públicos através da aplicação. Se o objectivo for descarga directa do S3, pode ser substituída por `url()`/CDN. Se for necessário controlo de tenancy, manter a rota pode ser preferível.

### 14.7 Nomes originais

Os uploads PAP novos usam nomes gerados pelo Laravel, enquanto os nomes originais são mantidos em colunas próprias. Confirmar se versões antigas guardadas com nomes originais precisam de normalização durante a migração.

## 15. Checklist de aceitação para outro agente

- [ ] Confirmar que `FILESYSTEM_DISK=s3` está activo no ambiente de produção.
- [ ] Confirmar bucket, região, endpoint e modo path-style.
- [ ] Confirmar que `public` resolve para prefixo público S3.
- [ ] Confirmar que `private` resolve para prefixo privado S3.
- [ ] Confirmar que objectos privados não têm URL pública.
- [ ] Confirmar que a credencial não está no repositório.
- [ ] Confirmar política IAM mínima.
- [ ] Confirmar política do bucket e CORS.
- [ ] Migrar ficheiros existentes com backup.
- [ ] Validar caminhos existentes na base de dados.
- [ ] Testar upload de logo.
- [ ] Testar substituição e remoção de logo.
- [ ] Testar upload de documento de curso.
- [ ] Testar substituição de documento de curso.
- [ ] Testar submissão de trabalho PAP.
- [ ] Testar download e visualização PAP.
- [ ] Testar upload/download de correcção PAP.
- [ ] Testar geração de recibo.
- [ ] Testar visualização e exportação de recibo.
- [ ] Testar anexo de recibo no email.
- [ ] Testar logo em recibo PDF.
- [ ] Testar logo em relatório de propinas.
- [ ] Testar isolamento entre tenants.
- [ ] Resolver ou documentar as falhas preexistentes da suite completa.
- [ ] Remover diagnósticos locais obsoletos de `root`/`is_writable` no serviço PAP.

## 16. Veredicto

**A implementação de código S3 está funcional para os fluxos migrados, mas a migração de produção ainda não está concluída.**

O próximo agente deve concentrar a avaliação em três áreas:

1. migração e validação dos ficheiros antigos;
2. isolamento físico por tenant;
3. configuração real do bucket, IAM, visibilidade e políticas de acesso.

Os ficheiros internos da aplicação que continuam locais não contradizem o objectivo de mover os documentos enviados pelos utilizadores para S3; são templates, assets e dados de runtime distintos.

## 17. Verificação do bug de visibility em discos scoped

### 17.1 Objectivo

Foi verificado empiricamente o bug histórico em que a opção `visibility` definida no disco `scoped` podia ser ignorada e não ser propagada para o disco base.

O teste foi criado em:

```text
tests/Unit/S3ScopedDiskVisibilityTest.php
```

### 17.2 Versões confirmadas

Comandos executados:

```bash
composer show laravel/framework --format=json
composer show league/flysystem-path-prefixing --format=json
composer outdated league/flysystem-path-prefixing --direct --format=json
composer outdated laravel/framework --direct --format=json
```

Resultado:

| Pacote | Versão instalada | Reference | Estado de actualização |
| --- | --- | --- | --- |
| `laravel/framework` | `v13.31.0` | `7c75fbf93f91fa077d3df1c820cc14f4e59a9774` | É a versão mais recente disponível |
| `league/flysystem-path-prefixing` | `3.31.0` | `d7f667c2d9d6684b74f30c6ad81ae7a0c23232f3` | É a versão mais recente disponível |

O Laravel instalado é superior ao Laravel 10.0, onde a correcção histórica foi incorporada.

O objecto Git curto `3365194` não existe no clone local de `vendor/laravel/framework`, portanto não foi possível provar a relação de ancestry através de `git merge-base`. Em vez de assumir com base apenas na versão, foi inspeccionado o código instalado.

### 17.3 Evidência no código instalado

Em `vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemManager.php`, no método `createScopedDriver()`, a implementação actual faz:

```php
if (isset($config['visibility'])) {
    $parent['visibility'] = $config['visibility'];
}
```

Também propaga `throw` para a configuração do disco pai. Isto confirma que a configuração de `visibility` do scoped é aplicada ao S3 base antes de o driver ser criado.

### 17.4 Estratégia do teste

O teste usa:

- o `FilesystemManager` e `Storage` reais da aplicação;
- o driver `scoped` real;
- o adapter S3 real do Flysystem;
- `Aws\MockHandler` para impedir chamadas de rede e não usar credenciais reais.

O mock regista o comando e o object key enviado ao S3. Para `GetObjectAcl`, devolve uma ACL pública quando a key começa por `public/` e uma ACL privada nos restantes casos.

Na mesma execução, o teste:

1. Resolve `Storage::disk('public')`.
2. Grava `scoped-public-visibility.txt`.
3. Consulta `getVisibility()` e espera `public`.
4. Resolve `Storage::disk('private')`.
5. Grava `scoped-private-visibility.txt`.
6. Consulta `getVisibility()` e espera `private`.
7. Resolve depois `Storage::disk('s3')`.
8. Grava `base-visibility.txt`.
9. Confirma que as keys enviadas são `public/scoped-public-visibility.txt`, `private/scoped-private-visibility.txt` e `base-visibility.txt`.
10. Confirma que a key do disco base não começa por `public/` nem `private/`.
11. Apaga os três objectos no bloco `finally` e limpa os discos da cache do `Storage`.

### 17.5 Resultado exacto

Comando:

```bash
vendor/bin/pest tests/Unit/S3ScopedDiskVisibilityTest.php --compact
```

Output:

```text
Tests:    1 passed
Assertions: 10
Duration:  177 ms
```

Também foi confirmada a sintaxe:

```text
No syntax errors detected in tests/Unit/S3ScopedDiskVisibilityTest.php
```

### 17.6 Conclusão e decisão técnica

**O bug não afecta a instalação actual.**

Os testes provaram que:

- `public` aplica visibilidade pública;
- `private` aplica visibilidade privada;
- a resolução dos discos scoped não contamina o disco base com prefixos;
- os object keys enviados ao adapter são independentes na mesma execução.

Não foi necessário:

- actualizar `laravel/framework`;
- actualizar `league/flysystem-path-prefixing`;
- aplicar workaround com `put($path, $content, 'public')` ou `put($path, $content, 'private')`;
- alterar a configuração para discos inline;
- alterar o comportamento funcional de `public` ou `private`.

A configuração actual com:

```php
'driver' => 'scoped',
'disk' => 's3',
'prefix' => env('AWS_PUBLIC_PREFIX', 'public'),
'visibility' => 'public',
```

e o equivalente privado está validada empiricamente para as versões instaladas.