<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Conta criada com sucesso</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #f1f3f4;
            font-family: 'Google Sans', Roboto, Arial, sans-serif;
            font-size: 14px;
            color: #202124;
            padding: 24px 0;
        }
        .email-wrapper {
            max-width: 480px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        }
        .header { text-align: center; padding: 32px 40px 24px; }
        .header img.logo { width: 75px; height: 24px; object-fit: contain; margin-bottom: 20px; }
        .divider { border: none; border-top: 1px solid #e8eaed; margin: 0 40px; }
        .credential-item { display: flex; align-items: center; gap: 16px; padding: 10px 24px; }
        .credential-item .item-text { min-width: 0; }
        .credential-item .item-text .label { font-size: 14px; color: #202124; word-break: break-word; }
        .credential-item .item-text .sublabel { font-size: 12px; color: #5f6368; margin-top: 2px; }
        .security-note {
            margin: 20px 24px;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .security-note .title {
            font-size: 13px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        .security-note p { font-size: 12px; color: #5f6368; line-height: 1.5; }
        .cta-wrapper { text-align: center; padding: 8px 24px 24px; }
        .cta-button {
            display: inline-block;
            background: #1a73e8;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 24px;
            border-radius: 4px;
            letter-spacing: 0.25px;
        }
        .access-link { padding: 0 24px 20px; font-size: 13px; color: #202124; line-height: 1.5; word-break: break-all; }
        .access-link a { color: #1a73e8; text-decoration: none; }
        .footer { padding: 16px 24px; border-top: 1px solid #e8eaed; text-align: center; }
        .footer p { font-size: 11px; color: #5f6368; line-height: 1.6; }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            @if (! empty($logoUrl))
                <img class="logo" src="{{ $logoUrl }}" alt="{{ $instituicao?->nome ?? config('app.name') }}">
            @endif
        </div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            A sua conta {{ $artigoInstituicao }} <strong>{{ $instituicao?->nome ?? config('app.name') }}</strong> foi criada com sucesso.
            Utilize as credenciais abaixo para aceder à plataforma.
        </p>

        <p style="padding: 0 24px 12px; font-size: 13px; color: #202124;">
            Dados de acesso à sua conta
        </p>

        @if (! empty($email))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Email de acesso</div>
                    <div class="sublabel">{{ $email }}</div>
                </div>
            </div>
        @endif

        @if (! empty($password))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Password de acesso</div>
                    <div class="sublabel">{{ $password }}</div>
                </div>
            </div>
        @endif

        <div class="security-note">
            <div>
                <div class="title">Proteja a sua conta</div>
                <p>
                    Por motivos de segurança, recomendamos que altere a sua password após realizar o primeiro acesso e que não partilhe as suas credenciais com terceiros.
                </p>
            </div>
        </div>

        <div class="cta-wrapper">
            <a href="{{ $url }}" class="cta-button" target="_blank">
                Aceder à plataforma
            </a>
        </div>

        <p class="access-link">
            Se o botão acima não funcionar, copie e cole o seguinte endereço no seu navegador:
            <br><br>
            <a href="{{ $url }}" target="_blank">{{ $url }}</a>
        </p>

        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela {{ config('app.name') }}.
                Por favor, não responda directamente a esta mensagem.
            </p>
        </div>
    </div>
</body>
</html>
