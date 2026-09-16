<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Redefinição da password</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .header {
            text-align: center;
            padding: 32px 40px 24px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 400;
            color: #202124;
            line-height: 1.4;
        }

        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        .message {
            padding: 24px 24px 12px;
            line-height: 1.6;
        }

        .message p + p {
            margin-top: 12px;
        }

        .security-note {
            margin: 12px 24px 20px;
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

        .security-note p {
            font-size: 12px;
            color: #5f6368;
            line-height: 1.5;
        }

        .cta-wrapper {
            text-align: center;
            padding: 8px 24px 24px;
        }

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

        .access-link {
            padding: 0 24px 20px;
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
            word-break: break-all;
        }

        .access-link a {
            color: #1a73e8;
            text-decoration: none;
        }

        .footer {
            padding: 16px 24px;
            border-top: 1px solid #e8eaed;
            text-align: center;
        }

        .footer p {
            font-size: 11px;
            color: #5f6368;
            line-height: 1.6;
        }

        .footer .company {
            margin-top: 8px;
            font-size: 11px;
            color: #80868b;
        }

        @media only screen and (max-width: 520px) {
            body {
                padding: 0;
            }

            .email-wrapper {
                width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>Redefinição da sua password</h1>
        </div>

        <hr class="divider">

        <div class="message">
            <p>Olá, <strong>{{ $nome }}</strong>!</p>

            <p>
                Recebemos um pedido para redefinir a password da sua conta na plataforma
                {{ config('app.name') }}.
            </p>

            <p>
                Clique no botão abaixo para escolher uma nova password. Este link é válido durante 60 minutos.
            </p>
        </div>

        <div class="security-note">
            <div class="title">Não pediu esta alteração?</div>
            <p>
                Pode ignorar este email. A sua password permanecerá inalterada e nenhuma ação será necessária.
            </p>
        </div>

        <div class="cta-wrapper">
            <a href="{{ $url }}" class="cta-button" target="_blank">
                Redefinir password
            </a>
        </div>
        
        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela plataforma {{ config('app.name') }}.
                Por favor, não responda directamente a esta mensagem.
            </p>

            <p class="company">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>

</html>
