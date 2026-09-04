# Resolver falhas de upload no Valet + PHP-FPM + AppArmor

Este guia resolve o erro de upload de ficheiros PDF no ambiente local com Valet Linux, PHP-FPM e AppArmor.

Foi escrito para ser seguido por qualquer colega, mesmo sem experiência profunda em Linux.

## Sintomas

O problema pode aparecer de várias formas:

- O formulário mostra `validation.uploaded`.
- O formulário diz que o servidor não conseguiu receber o ficheiro.
- Ficheiros pequenos, com apenas alguns KB, também falham.
- O modal fecha sem erro, mas o ficheiro não aparece na lista.
- São criados ficheiros com `0 bytes` no armazenamento.
- A base de dados pode ficar com o caminho `0`.

Quando ficheiros pequenos falham, não se deve começar por aumentar apenas o limite de MB. É necessário verificar o PHP-FPM e o AppArmor.

## Causa

Há três problemas que podem ocorrer juntos:

1. O PHP-FPM não tem um diretório temporário de upload configurado.
2. O perfil AppArmor bloqueia o PHP-FPM ao criar `/tmp/php...` ou ao aplicar locks nos ficheiros do projeto.
3. O disco Laravel `public` pode estar configurado com `throw=false`. Nesse caso, `store()` devolve `false` sem lançar exceção. O código pode continuar como se tivesse funcionado e gravar `0` como caminho.

O código de erro mais importante é:

```text
UPLOAD_ERR_NO_TMP_DIR = 6
File could not be uploaded: missing temporary directory.
```

Outro indicador importante aparece no log do kernel:

```text
apparmor="DENIED" operation="mknod" profile="php-fpm" name="/tmp/php..."
apparmor="DENIED" operation="file_lock" profile="php-fpm"
```

## Pré-requisitos

Os comandos abaixo assumem:

- Ubuntu ou distribuição semelhante.
- PHP 8.4.
- Valet Linux.
- Projeto Laravel servido por um domínio como `imcl.sge.localhost`.
- Pool PHP-FPM chamado `valet`.

Se a versão do PHP for diferente, substitua `8.4` nos comandos pela versão instalada.

Confirme a versão:

```bash
php -v
```

## Passo 1: confirmar o erro

Na aplicação, tente fazer um upload pequeno e consulte o log Laravel:

```bash
tail -50 storage/logs/laravel.log
```

Procure por:

```text
erro: 6
missing temporary directory
```

Depois consulte os bloqueios do kernel:

```bash
journalctl -k --since '30 minutes ago' --no-pager | grep -E 'apparmor|php-fpm|/tmp/php'
```

Se aparecer `profile="php-fpm"` com `operation="mknod"` ou `operation="file_lock"`, siga todos os passos deste guia.

## Passo 2: confirmar o diretório temporário

Confirme que `/tmp` existe e pode ser escrito pelo utilizador do pool Valet:

```bash
ls -ld /tmp
sudo -u "$USER" test -d /tmp && echo 'diretorio existe'
sudo -u "$USER" test -w /tmp && echo 'diretorio gravavel'
```

O resultado esperado para `/tmp` é semelhante a:

```text
drwxrwxrwt ... /tmp
```

O bit final `t` é importante. Não remova as permissões existentes de `/tmp` sem saber o que está a fazer.

## Passo 3: configurar o pool PHP-FPM do Valet

O `.user.ini` do projeto é útil como fallback, mas no Valet o PHP executa um front-controller global. Por isso, a configuração mais importante deve ficar no pool `valet`.

Faça uma cópia de segurança:

```bash
sudo cp /etc/php/8.4/fpm/pool.d/valet.conf \
  /etc/php/8.4/fpm/pool.d/valet.conf.bak.$(date +%Y%m%d%H%M%S)
```

Abra o ficheiro:

```bash
sudo nano /etc/php/8.4/fpm/pool.d/valet.conf
```

Dentro da secção `[valet]`, adicione estas linhas:

```ini
php_admin_value[upload_tmp_dir] = /tmp
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 35M
php_admin_value[max_file_uploads] = 20
```

`post_max_size` deve ser maior que a soma máxima dos ficheiros enviados no mesmo pedido. Neste projeto, podem ser enviados três documentos de até 10 MB, por isso `35M` deixa margem para os dados do pedido.

## Passo 4: configurar o fallback do projeto

Na raiz do projeto, crie ou edite:

```text
public/.user.ini
```

Conteúdo recomendado:

```ini
file_uploads = On
upload_tmp_dir = /tmp
upload_max_filesize = 20M
post_max_size = 35M
max_file_uploads = 20
```

Esta configuração ajuda noutros ambientes FastCGI, mas não substitui a configuração do pool Valet.

## Passo 5: permitir temporários no AppArmor

Confirme se o perfil existe:

```bash
sudo aa-status | grep -A1 php-fpm
sudo test -f /etc/apparmor.d/php-fpm && echo 'perfil encontrado'
```

O perfil principal inclui o ficheiro local:

```text
/etc/apparmor.d/local/php-fpm
```

Faça uma cópia de segurança:

```bash
sudo cp /etc/apparmor.d/local/php-fpm \
  /etc/apparmor.d/local/php-fpm.bak.$(date +%Y%m%d%H%M%S)
```

Edite o override:

```bash
sudo nano /etc/apparmor.d/local/php-fpm
```

Preserve as regras que já existirem e garanta que estas regras estão presentes:

```text
/tmp/ rw,
/tmp/php* rw,
```

Estas regras permitem ao PHP-FPM criar e escrever os ficheiros temporários usados durante o upload.

## Passo 6: permitir locks no armazenamento

O Laravel pode usar locks ao escrever os ficheiros. No override AppArmor, a regra geral do diretório pessoal deve permitir `k`:

```text
/home/marq/** rwk,
```

Substitua `marq` pelo seu utilizador Linux.

Se o override já tiver esta regra:

```text
/home/marq/** rw,
```

troque-a por:

```text
/home/marq/** rwk,
```

A permissão `k` é a permissão de lock. Sem ela, podem surgir ficheiros vazios e mensagens `operation="file_lock"` no kernel.

## Passo 7: validar e recarregar

Primeiro valide o perfil AppArmor:

```bash
sudo apparmor_parser -r /etc/apparmor.d/php-fpm
```

Se não houver mensagem de erro, valide o PHP-FPM:

```bash
sudo php-fpm8.4 -t
```

Depois recarregue o serviço:

```bash
sudo systemctl reload php8.4-fpm
```

Confirme que as diretivas estão carregadas no pool Valet:

```bash
sudo php-fpm8.4 -tt 2>&1 | grep -E \
  'php_admin_value\\[(upload_tmp_dir|upload_max_filesize|post_max_size|max_file_uploads)\\]'
```

O resultado esperado inclui:

```text
php_admin_value[upload_tmp_dir] = /tmp
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 35M
php_admin_value[max_file_uploads] = 20
```

Confirme também que o perfil está ativo:

```bash
sudo aa-status | grep -A1 php-fpm
```

## Passo 8: testar o upload

1. Atualize completamente a página no navegador.
2. Selecione um PDF pequeno.
3. Faça o upload.
4. Confirme que o documento aparece na lista.
5. Confirme que o ficheiro não tem tamanho zero.

No projeto, pode verificar o armazenamento tenant com:

```bash
find storage/tenant*/app/public -type f -mmin -10 \
  -printf '%s bytes %p\\n'
```

O resultado deve mostrar um tamanho maior que `0 bytes`.

Também pode confirmar os caminhos do curso na base de dados tenant. O valor deve ser um caminho semelhante a:

```text
cursos-tutelados/<id>/criterios-pap/<nome-gerado>.pdf
```

O valor `0` indica uma tentativa antiga em que o disco devolveu `false`.

## Se ainda falhar

Consulte imediatamente os novos bloqueios, usando apenas o intervalo desde o último teste:

```bash
journalctl -k --since '2 minutes ago' --no-pager | grep 'profile="php-fpm"'
```

Interpretação rápida:

| Mensagem | Causa provável | Ação |
| --- | --- | --- |
| `operation="mknod" name="/tmp/php..."` | AppArmor bloqueia temporários | Rever `/tmp/ rw,` e `/tmp/php* rw,` |
| `operation="file_lock"` | AppArmor bloqueia locks | Rever `/home/<utilizador>/** rwk,` |
| `missing temporary directory` sem bloqueio AppArmor | `upload_tmp_dir` não carregado | Rever o pool `valet` e recarregar FPM |
| `0 bytes` no storage | `store()` falhou silenciosamente | Rever AppArmor e usar `throw=true` ou verificar retorno |
| `validation.uploaded` com ficheiro grande | Limite PHP/proxy | Rever `upload_max_filesize`, `post_max_size` e `client_max_body_size` |

Verifique também que o domínio usa o socket Valet esperado:

```bash
sudo nginx -T 2>/dev/null | grep -E 'server_name|fastcgi_pass|SCRIPT_FILENAME' | head -50
```

Para o Valet, deve aparecer algo semelhante a:

```text
fastcgi_pass unix:/home/<utilizador>/.config/valet/valet84.sock;
```

## Nota sobre os ficheiros `0 bytes` antigos

As tentativas feitas antes da correção podem ter deixado ficheiros vazios no diretório tenant. Eles não são documentos válidos.

Depois de confirmar que o upload novo funciona, podem ser removidos apenas os ficheiros vazios do curso afetado:

```bash
find storage/tenantimcl/app/public/cursos-tutelados/<id-do-curso> \
  -type f -size 0 -delete
```

Não remova ficheiros sem confirmar primeiro o caminho e o tamanho.

## Resumo rápido

Para uma instalação Valet com PHP 8.4, a sequência essencial é:

```bash
sudo nano /etc/php/8.4/fpm/pool.d/valet.conf
sudo nano /etc/apparmor.d/local/php-fpm
sudo apparmor_parser -r /etc/apparmor.d/php-fpm
sudo php-fpm8.4 -t
sudo systemctl reload php8.4-fpm
```

O PHP-FPM precisa de:

```ini
php_admin_value[upload_tmp_dir] = /tmp
```

O AppArmor precisa de permitir:

```text
/tmp/ rw,
/tmp/php* rw,
/home/<utilizador>/** rwk,
```

Depois do reload, testar sempre com um PDF pequeno e confirmar que o ficheiro criado tem tamanho maior que zero.
