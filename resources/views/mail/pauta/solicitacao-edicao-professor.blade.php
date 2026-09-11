<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Pedido de edição de pauta submetido</title>

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
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .header {
            text-align: center;
            padding: 32px 40px 24px;
        }

        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        .info-banner {
            margin: 24px 24px 20px;
            background: #e8f0fe;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .info-banner p {
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
        }

        .info-banner p strong { font-weight: 500; }

        .section-label {
            padding: 0 24px 12px;
            font-size: 13px;
            color: #202124;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 10px 24px;
        }

        .detail-item .item-text .label {
            font-size: 14px;
            color: #202124;
        }

        .detail-item .item-text .sublabel {
            font-size: 12px;
            color: #5f6368;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-reabertura {
            background: #fce8e6;
            color: #c5221f;
        }

        .badge-extensao {
            background: #e8f0fe;
            color: #1a73e8;
        }

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
            body { padding: 0; }
            .email-wrapper { width: 100%; border-radius: 0; }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">

        <div class="header"></div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nomeProfessor }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            O seu pedido de <strong>{{ $tipoLabel }}</strong> foi submetido com sucesso e aguarda
            aprovação do director da instituição.
        </p>

        {{-- Info Banner --}}
        <div class="info-banner">
            <p>
                Será notificado assim que o director tomar uma decisão relativamente ao seu pedido.
            </p>
        </div>

        {{-- Detalhes do pedido --}}
        <p class="section-label">Detalhes do pedido</p>

        <div class="detail-item">
            <div class="item-text">
                <div class="label">Tipo de pedido</div>
                <div class="sublabel">
                    <span class="badge {{ $tipo === 'reabertura_edicao' ? 'badge-reabertura' : 'badge-extensao' }}">
                        {{ $tipoLabel }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-item">
            <div class="item-text">
                <div class="label">Disciplina</div>
                <div class="sublabel">{{ $disciplina }}</div>
            </div>
        </div>

        <div class="detail-item">
            <div class="item-text">
                <div class="label">Turma</div>
                <div class="sublabel">{{ $turma }}</div>
            </div>
        </div>

        <div class="detail-item">
            <div class="item-text">
                <div class="label">Período</div>
                <div class="sublabel">{{ $periodo }}º Período</div>
            </div>
        </div>

        <div class="detail-item">
            <div class="item-text">
                <div class="label">Motivo</div>
                <div class="sublabel">{{ $motivo }}</div>
            </div>
        </div>

        {{-- Nota --}}
        <div class="security-note">
            <div class="title">O que acontece a seguir?</div>
            <p>
                O director irá analisar o seu pedido. Se aprovado, poderá voltar a aceder
                à pauta para efectuar as alterações necessárias dentro do prazo definido.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela
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