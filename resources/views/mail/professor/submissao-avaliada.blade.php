<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Submissão {{ $aprovado ? 'aprovada' : 'rejeitada' }}</title>
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
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin: 4px 24px 20px;
            {{ $aprovado
                ? 'background: #e6f4ea; color: #137333; border: 1px solid #ceead6;'
                : 'background: #fce8e6; color: #b3261e; border: 1px solid #f4c7c3;' }}
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

        .quote-box {
            margin: 12px 24px 20px;
            background: #f8faff;
            border-left: 3px solid {{ $aprovado ? '#137333' : '#b3261e' }};
            border-radius: 4px;
            padding: 14px 18px;
        }

        .quote-box .label {
            font-size: 12px;
            color: {{ $aprovado ? '#137333' : '#b3261e' }};
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .quote-box .text {
            font-size: 13px;
            color: #202124;
            line-height: 1.6;
            font-style: italic;
        }

        .security-note {
            margin: 20px 24px;
            background: {{ $aprovado ? '#e8f0fe' : '#fef7e0' }};
            border: 1px solid {{ $aprovado ? '#c5d4f5' : '#fde293' }};
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
            body { padding: 0; }
            .email-wrapper { width: 100%; border-radius: 0; }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        @include('mail.partials.institution-logo')

        <div class="header">
            <h1>Submissão {{ $aprovado ? 'aprovada' : 'rejeitada' }}</h1>
            <div class="account-badge">
                {{ $instituicao->nome ?? 'SGE' }}
            </div>
        </div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 16px; font-size: 14px; line-height: 1.6; color: #202124;">
            A sua submissão foi avaliada pelo diretor.
        </p>

        <div>
            <div class="status-badge">
                {{ $aprovado ? '✓ Aprovada' : '✕ Rejeitada' }}
            </div>
        </div>

        <p class="section-label">Detalhes da submissão</p>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Prazo</div>
                <div class="sublabel">{{ $prazoTitulo ?? '—' }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Disciplina</div>
                <div class="sublabel">{{ $disciplina ?? '—' }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Classe</div>
                <div class="sublabel">{{ $classe ?? '—' }}</div>
            </div>
        </div>

        @if(!empty($turmaNome))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Turma</div>
                    <div class="sublabel">{{ $turmaNome }}</div>
                </div>
            </div>
        @endif

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Versão</div>
                <div class="sublabel">v{{ $versao }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Data da submissão</div>
                <div class="sublabel">{{ $dataSubmissao }}</div>
            </div>
        </div>

        @if(!empty($parecer))
            <div class="quote-box">
                <div class="label">Parecer do diretor</div>
                <div class="text">"{{ $parecer }}"</div>
            </div>
        @endif

        @if($aprovado)
            <div class="security-note">
                <div class="text">
                    <div class="title">Submissão validada</div>
                    <p>A sua submissão foi aceite pelo diretor. Não é necessária mais nenhuma ação.</p>
                </div>
            </div>
        @else
            <div class="security-note">
                <div class="text">
                    <div class="title">Próximos passos</div>
                    <p>Enquanto o prazo estiver aberto, pode corrigir e submeter uma nova versão da prova.</p>
                </div>
            </div>
        @endif

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ $instituicao->nome }}. Por favor, não responda
                directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>

    </div>
</body>

</html>