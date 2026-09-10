<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Confirmação de matrícula</title>

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

        .account-badge {
            display: inline-block;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 20px;
            padding: 6px 12px;
            margin-top: 12px;
            font-size: 13px;
            color: #202124;
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

        .item-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f3f4;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #5f6368;
        }

        .item-text .label {
            font-size: 14px;
            color: #202124;
            word-break: break-word;
        }

        .item-text .sublabel {
            font-size: 12px;
            color: #5f6368;
            margin-top: 2px;
        }

        .security-note {
            margin: 20px 24px;
            background: #e8f0fe;
            border: 1px solid #c5d4f5;
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
        <hr class="divider">

        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            A sua matrícula {{ $artigoInstituicao }} <strong>{{ $instituicao->nome }}</strong> foi confirmada com
            sucesso.
        </p>

        <p class="section-label">Detalhes da confirmação</p>

        @if($curso)
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Curso</div>
                    <div class="sublabel">{{ $curso }}</div>
                </div>
            </div>
        @endif

        @if($classe)
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Classe</div>
                    <div class="sublabel">{{ $classe }}</div>
                </div>
            </div>
        @endif

        @if($turno)
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Turno</div>
                    <div class="sublabel">{{ $turno }}</div>
                </div>
            </div>
        @endif

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Turma atribuída</div>
                <div class="sublabel">{{ $nomeTurma }}</div>
            </div>
        </div>
        <div class="security-note">
            <div class="text">
                <div class="title">Informação</div>
                <p>Se tiver alguma dúvida sobre a sua matrícula ou a turma atribuída, contacte a secretaria da
                    instituição.</p>
            </div>
        </div>

        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela plataforma
                {{ config('app.name') }}.
                Por favor, não responda directamente a esta mensagem.
            </p>
            <p class="company">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>

</html>