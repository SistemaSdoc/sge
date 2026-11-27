# TenantUrlGenerator: finalidade e utilização

## Finalidade

`App\Services\Tenant\TenantUrlGenerator` gera URLs absolutos para rotas tenant, evitando que os emails usem acidentalmente o host da request atual, o domínio central ou `localhost`.

O serviço aplica uma regra comum:

1. Usa o domínio explicitamente indicado, se recebido.
2. Caso contrário, procura o domínio do tenant ativo.
3. Gera a rota nomeada com `tenant_route()`.
4. Aplica o esquema definido em `config('app.url')` (por exemplo, `https`).
5. Lança `LogicException` se não houver domínio ou esquema configurado. Não substitui esses dados por um fallback central.

Código: [TenantUrlGenerator](../app/Services/Tenant/TenantUrlGenerator.php).

## Utilização direta

O método público é:

```php
route(string $routeName, array $parameters = [], ?string $domain = null): string
```

### Dentro do tenant destinatário

Quando o tenant correto já está ativo, o domínio pode ser omitido. O serviço resolve-o a partir do tenant:

```php
$url = app(TenantUrlGenerator::class)->route(
    'tenant.dashboard',
);
```

Com parâmetros da rota:

```php
$url = app(TenantUrlGenerator::class)->route(
    'tenant.dashboard.instituicoes.cursos-tutelados.show',
    [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
    ],
);
```

### Para um tenant explícito ou um fluxo entre tenants

Quando o destinatário pertence a outro tenant — ou quando o código corre num contexto central — obtenha o domínio desse destinatário e passe-o explicitamente:

```php
$domain = $tenantDestinatario->domains()->value('domain');

$url = app(TenantUrlGenerator::class)->route(
    'tenant.dashboard.notificacoes.show',
    ['notification' => $notification->id],
    $domain,
);
```

Se não existir domínio registado, a geração lança `LogicException`. Não capture essa exceção para construir manualmente um URL alternativo com `route()` ou `url()`: isso voltaria a introduzir o risco de host incorreto. Trate o erro no ponto apropriado do fluxo de envio e registe o contexto necessário para corrigir o tenant.

## Utilização em notificações

As notificações podem usar o trait [TenantNotificationUrl](../app/Notifications/Concerns/TenantNotificationUrl.php), que delega no serviço:

```php
use App\Notifications\Concerns\TenantNotificationUrl;

class NotificacaoExemplo extends Notification
{
    use TenantNotificationUrl;

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->view('mail.exemplo', [
            'url' => $this->tenantNotificationUrl(
                'tenant.dashboard',
            ),
        ]);
    }
}
```

Se o email for enviado a um utilizador de outro tenant, passe o domínio do destinatário:

```php
'url' => $this->tenantNotificationUrl(
    'tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show',
    $parametrosDaRota,
    $tenantTutor->domains()->value('domain'),
),
```

O trait mantém as notificações mais simples e centraliza a implementação no mesmo serviço.

## Escolha da rota e do destinatário

O gerador resolve o **host e o esquema**, mas não escolhe a página correta nem verifica permissões. Quem chama o serviço deve:

- selecionar uma rota tenant existente e adequada ao tipo de utilizador destinatário;
- fornecer todos os parâmetros obrigatórios da rota;
- passar o domínio do tenant onde o destinatário está registado em fluxos cross-tenant;
- garantir que o caminho pode ser acedido pelo destinatário segundo as regras de autorização da aplicação.

Por exemplo, no fluxo PAP entre colégio e tutora, usar o domínio da tutora com uma rota genérica do colégio não é suficiente. O nome da rota tem de corresponder também à página cross-tenant que permite à tutora consultar o grupo.

## Dependências de configuração

- `config('app.url')` tem de conter o esquema canónico da aplicação, normalmente `https`.
- O tenant destinatário tem de ter um domínio registado em `domains`, salvo se o chamador passar o domínio explicitamente.
- O nome da rota deve estar registado e ser uma rota tenant compatível com `tenant_route()`.

Não é necessário que o host em `APP_URL` seja o host final do email tenant; neste serviço, `APP_URL` fornece o esquema. O domínio final vem do tenant.

## Testes

O teste [TenantNotificationUrlTest](../tests/Unit/Notifications/Concerns/TenantNotificationUrlTest.php) cobre:

- domínio explicitamente passado ao helper;
- esquema HTTPS definido por `config('app.url')`;
- erro explícito quando não há domínio e não deve haver fallback central.

Para novos fluxos de email, valide o URL completo — esquema, host, caminho e parâmetros — e, em particular, o domínio do destinatário nos cenários cross-tenant.

## Quando não utilizar

Este serviço destina-se a rotas do portal tenant. Não o use para:

- rotas centrais, que devem continuar a apontar para o portal central;
- URLs públicos que não pertençam a um tenant;
- URLs de ficheiros/imagens, que devem seguir a configuração do respetivo `Storage` disk.

Para o contexto e o inventário dos fluxos de email revistos, consulte o [relatório dos links de notificações](./RELATORIO_LINKS_NOTIFICACOES_EMAIL.md).
