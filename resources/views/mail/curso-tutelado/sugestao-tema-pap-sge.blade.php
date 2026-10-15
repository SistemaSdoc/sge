<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $titulo }}</title>

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

        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        .section-label {
            padding: 0 24px 12px;
            font-size: 13px;
            color: #202124;
        }

        .credential-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 24px;
        }

        .credential-item .item-text .label {
            font-size: 14px;
            color: #202124;
            word-break: break-word;
        }

        .credential-item .item-text .sublabel {
            font-size: 12px;
            color: #5f6368;
            margin-top: 2px;
        }

        .feedback-box {
            margin: 20px 24px 0;
            background: #fef9e7;
            border-left: 3px solid #f29900;
            border-radius: 4px;
            padding: 14px 16px;
        }

        .feedback-box .feedback-label {
            font-size: 13px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 6px;
        }

        .feedback-box p {
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
        }

        .cta-wrapper {
            text-align: center;
            padding: 8px 24px 24px;
            margin-top: 24px;
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
        @include('mail.partials.institution-logo')

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            {{ $mensagem }}
        </p>

        <br>
        <p class="section-label">Detalhes</p>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Curso</div>
                <div class="sublabel">{{ $nomeCurso }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Sugestão</div>
                <div class="sublabel">{{ $tituloSugestao }}</div>
            </div>
        </div>

        @if ($observacao)
            <div class="feedback-box">
                <div class="feedback-label">Observação</div>
                <p>{{ $observacao }}</p>
            </div>
        @endif

        <div class="cta-wrapper">
            <a href="{{ $url }}" class="cta-button" target="_blank">Abrir curso</a>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ config('app.name') }}. Por favor, não responda
                directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>