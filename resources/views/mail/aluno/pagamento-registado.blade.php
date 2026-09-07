<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Pagamento registado com sucesso</title>

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

        /* ---- Header ---- */
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
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 20px;
            padding: 6px 12px;
            margin-top: 12px;
            font-size: 13px;
            color: #202124;
        }

        /* ---- Divider ---- */
        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        /* ---- Credentials ---- */
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

        .credential-item .item-text {
            min-width: 0;
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

        /* ---- Security Note ---- */
        .security-note {
            margin: 20px 24px;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
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

        /* ---- Footer ---- */
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

        {{-- Greeting --}}
        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            O seu pagamento {{ $artigoInstituicao }} <strong>{{ $instituicao->nome }}</strong> foi registado com
            sucesso.
            Abaixo encontra o resumo do pagamento.
        </p>

        {{-- Resumo do pagamento --}}
        <p class="section-label">
            Resumo do pagamento
        </p>

        @if (!empty($numeroRecibo))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Nº Recibo</div>
                    <div class="sublabel">{{ $numeroRecibo }}</div>
                </div>
            </div>
        @endif

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Valor pago</div>
                <div class="sublabel">{{ $valorTotal }} AOA</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Data</div>
                <div class="sublabel">{{ $dataPagamento }}</div>
            </div>
        </div>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Método de pagamento</div>
                <div class="sublabel">{{ $metodo }}</div>
            </div>
        </div>

        @if (!empty($referencia))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Referência</div>
                    <div class="sublabel">{{ $referencia }}</div>
                </div>
            </div>
        @endif

        {{-- Nota --}}
        <div class="security-note">
            <div class="text">
                <div class="title">Recibo em anexo</div>
                <p>
                    O recibo do seu pagamento encontra-se em anexo neste email em formato PDF.
                </p>
            </div>
        </div>

        {{-- Footer --}}
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