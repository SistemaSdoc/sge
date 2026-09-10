<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Decisão sobre pedido de edição de pauta</title>

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

        .header { text-align: center; padding: 32px 40px 24px; }

        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        .result-banner {
            margin: 24px 24px 20px;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .result-banner.aprovada { background: #e6f4ea; }
        .result-banner.rejeitada { background: #fce8e6; }

        .result-banner p {
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
        }

        .result-banner p strong { font-weight: 500; }

        .section-label {
            padding: 0 24px 12px;
            font-size: 13px;
            color: #202124;
        }

        .detail-item {
            padding: 10px 24px;
        }

        .detail-item .label {
            font-size: 14px;
            color: #202124;
        }

        .detail-item .sublabel {
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

        .badge-reabertura { background: #fce8e6; color: #c5221f; }
        .badge-extensao   { background: #e8f0fe; color: #1a73e8; }

        .observacao-box {
            margin: 4px 24px 8px;
            background: #f8f9fa;
            border-left: 3px solid #dadce0;
            border-radius: 0 4px 4px 0;
            padding: 10px 14px;
            font-size: 13px;
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

        .footer p { font-size: 11px; color: #5f6368; line-height: 1.6; }
        .footer .company { margin-top: 8px; font-size: 11px; color: #80868b; }

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
            O director tomou uma decisão relativamente ao seu pedido de <strong>{{ $tipoLabel }}</strong>.
        </p>

        {{-- Banner resultado --}}
        <div class="result-banner {{ $aprovada ? 'aprovada' : 'rejeitada' }}">
            @if ($aprovada)
                <p>
                    <strong>Pedido aprovado.</strong>
                    @if ($prazoEdicaoAte)
                        Pode editar a pauta até <strong>{{ $prazoEdicaoAte }}</strong>.
                    @else
                        Já pode aceder à pauta para efectuar as alterações.
                    @endif
                </p>
            @else
                <p><strong>Pedido rejeitado.</strong> O director não aprovou a solicitação.</p>
            @endif
        </div>

        {{-- Detalhes --}}
        <p class="section-label">Detalhes do pedido</p>

        <div class="detail-item">
            <div class="label">Tipo de pedido</div>
            <div class="sublabel">
                <span class="badge {{ $tipo === 'reabertura_edicao' ? 'badge-reabertura' : 'badge-extensao' }}">
                    {{ $tipoLabel }}
                </span>
            </div>
        </div>

        <div class="detail-item">
            <div class="label">Disciplina</div>
            <div class="sublabel">{{ $disciplina }}</div>
        </div>

        <div class="detail-item">
            <div class="label">Turma</div>
            <div class="sublabel">{{ $turma }}</div>
        </div>

        <div class="detail-item">
            <div class="label">Período</div>
            <div class="sublabel">{{ $periodo }}º Período</div>
        </div>

        @if (!empty($observacao))
            <div class="detail-item">
                <div class="label">Observação do director</div>
            </div>
            <div class="observacao-box">{{ $observacao }}</div>
        @endif

        {{-- CTA só se aprovada --}}
        @if ($aprovada)
            <div class="cta-wrapper">
                <a href="{{ $urlPautas }}" class="cta-button" target="_blank">
                    Aceder às pautas
                </a>
            </div>
        @endif

        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela {{ config('app.name') }}.
                Por favor, não responda directamente a esta mensagem.
            </p>
            <p class="company">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>

    </div>
</body>

</html>