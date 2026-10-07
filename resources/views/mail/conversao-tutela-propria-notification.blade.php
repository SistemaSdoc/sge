<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Pedido de conversão para tutela própria</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f1f3f4;
            color: #202124;
            font-family: 'Google Sans', Roboto, Arial, sans-serif;
            font-size: 14px;
            padding: 24px 0;
        }

        .email-wrapper {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        }

        .header {
            padding: 32px 40px 24px;
            text-align: center;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 400;
            line-height: 1.4;
        }

        .divider {
            border: 0;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        .message {
            padding: 24px 24px 12px;
            line-height: 1.6;
        }

        .message p+p {
            margin-top: 12px;
        }

        .cta-wrapper {
            padding: 8px 24px 24px;
            text-align: center;
        }

        .cta-button {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 4px;
            background: #1a73e8;
            color: #fff !important;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }

        .access-link {
            padding: 0 24px 20px;
            font-size: 13px;
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
            color: #5f6368;
            font-size: 11px;
            line-height: 1.6;
        }

        .footer .company {
            margin-top: 8px;
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
            <h1>Pedido de conversão para tutela própria</h1>
        </div>
        <hr class="divider">
        <div class="message">
            <p>Olá!</p>
            <p>A <strong>{{ $nomeInstituicaoSolicitante }}</strong> solicitou deixar de ter tutela externa no curso
                <strong>{{ $nomeCurso }}</strong>.
            </p>
            <p>Entre na plataforma para aprovar ou rejeitar esta conversão.</p>
        </div>
        <div class="cta-wrapper"><a href="{{ $url }}" class="cta-button" target="_blank">Analisar solicitação</a></div>
        <p class="access-link">Se o botão acima não funcionar, copie e cole o seguinte endereço no seu
            navegador:<br><br><a href="{{ $url }}" target="_blank">{{ $url }}</a></p>
        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ config('app.name') }}. Por favor, não responda
                directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>