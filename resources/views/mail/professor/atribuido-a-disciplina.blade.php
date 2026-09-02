<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Atribuído a uma disciplina</title>
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

        .header .account-badge {
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 20px;
            padding: 6px 12px;
            margin-top: 12px;
            font-size: 13px;
            color: #202124;
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

        .credential-item .item-icon {
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

        .security-note {
            margin: 20px 24px;
            background: #e8f0fe;
            border: 1px solid #c5d4f5;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .security-note .text .title {
            font-size: 13px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }

        .security-note .text p {
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

        <div class="header">
            <h1>Atribuído a uma disciplina</h1>
            <div class="account-badge"><span>{{ $nome }}</span></div>
        </div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            Foi atribuído como professor {{ $artigoInstituicao }} <strong>{{ $instituicao->nome }}</strong> à seguinte
            disciplina.
        </p>

        <p class="section-label">Detalhes da atribuição</p>

        <div class="credential-item">
            <div class="item-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z" />
                </svg>
            </div>
            <div class="item-text">
                <div class="label">{{ $nomeDisciplina }}</div>
                <div class="sublabel">Disciplina</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                </svg>
            </div>
            <div class="item-text">
                <div class="label">{{ $nomeTurma }}</div>
                <div class="sublabel">Turma</div>
            </div>
        </div>

        <div class="security-note">
            <div class="text">
                <div class="title">Informação</div>
                <p>Já pode aceder à plataforma para consultar os detalhes da disciplina e os alunos da turma.</p>
            </div>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ $instituicao->nome }}. Por favor, não responda
                directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>

    </div>
</body>

</html>