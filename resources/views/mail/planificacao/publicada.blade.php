<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $primeira ? 'Planificação disponível' : 'Planificação atualizada' }}</title>
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

        .header h1 {
            font-size: 20px;
            font-weight: 400;
            color: #202124;
            line-height: 1.4;
        }

        .header .account-badge {
            display: inline-block;
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
            font-weight: 500;
        }

        .credential-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 24px;
        }

        .credential-item .item-text .label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 2px;
        }

        .credential-item .item-text .sublabel {
            font-size: 14px;
            color: #202124;
            font-weight: 500;
            word-break: break-word;
        }

        .badge-nova {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin: 4px 24px 20px;
        }

        .badge-nova.primeira {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }

        .badge-nova.atualizada {
            background: #e8f0fe;
            color: #1a73e8;
            border: 1px solid #c5d4f5;
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

        @media only screen and (max-width: 520px) {
            body { padding: 0; }
            .email-wrapper { width: 100%; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>{{ $primeira ? 'Nova planificação' : 'Planificação atualizada' }}</h1>
            <div class="account-badge">{{ $instituicao->nome ?? 'SGE' }}</div>
        </div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 16px; font-size: 14px; line-height: 1.6; color: #202124;">
            {{ $primeira
                ? 'Foi publicada uma nova planificação que lhe pode interessar.'
                : 'A planificação abaixo foi atualizada pelo diretor.' }}
        </p>

        <div>
            <span class="badge-nova {{ $primeira ? 'primeira' : 'atualizada' }}">
                {{ $primeira ? '✦ Nova' : '↻ Atualizada' }} • v{{ $versao }}
            </span>
        </div>

        <p class="section-label">Detalhes</p>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Título</div>
                <div class="sublabel">{{ $titulo }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Disciplina</div>
                <div class="sublabel">{{ $disciplina }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Classe</div>
                <div class="sublabel">{{ $classe }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Período</div>
                <div class="sublabel">{{ $periodo }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Ano letivo</div>
                <div class="sublabel">{{ $anoLetivo }}</div>
            </div>
        </div>

        <div class="security-note">
            <div class="text">
                <div class="title">Acesso ao documento</div>
                <p>Aceda à plataforma para visualizar ou descarregar o ficheiro.</p>
            </div>
        </div>

        <div class="cta-wrapper">
            <a href="{{ $url }}" class="cta-button" target="_blank">Ver planificação</a>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ $instituicao->nome ?? config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>


