<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Código de confirmação para acesso às notas</title>
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

        .code-panel {
            margin: 12px 24px 20px;
            padding: 18px 16px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 8px;
        }

        .code-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 8px;
        }

        .code {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 6px;
            color: #202124;
            padding-left: 6px;
        }

        .security-note {
            margin: 0 24px 20px;
            background: #fef7e0;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .security-note .title {
            font-size: 13px;
            font-weight: 500;
            color: #e37400;
            margin-bottom: 4px;
        }

        .security-note p {
            font-size: 12px;
            color: #5f6368;
            line-height: 1.5;
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

            .code {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        @include('mail.partials.institution-logo')

        <div class="header">
            <h1>Confirme o acesso às notas</h1>
        </div>

        <hr class="divider">

        <div class="message">
            <p>Olá, <strong>{{ $nome }}</strong>!</p>
            <p>Use o código abaixo para confirmar a sua identidade e continuar na área de notas da plataforma {{ config('app.name') }}.</p>
        </div>

        <div class="code-panel">
            <p class="code-label">Código de confirmação</p>
            <p class="code">{{ $code }}</p>
        </div>

        <div class="security-note">
            <div class="title">Código válido por 10 minutos</div>
            <p>Este código só pode ser usado uma vez. Não o partilhe com ninguém. Se não solicitou este acesso, ignore esta mensagem.</p>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ config('app.name') }}. Por favor, não responda directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>