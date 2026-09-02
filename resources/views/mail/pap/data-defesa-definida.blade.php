<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Data de defesa definida</title>

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

        .header img.logo {
            width: 75px;
            height: 24px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        /* ---- Divider ---- */
        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        /* ---- Info Card ---- */
        .info-card {
            margin: 0 24px;
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .info-card .card-row {
            margin-bottom: 12px;
        }

        .info-card .card-row:last-child {
            margin-bottom: 0;
        }

        .info-card .card-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 2px;
        }

        .info-card .card-value {
            font-size: 14px;
            font-weight: 500;
            color: #202124;
        }

        /* ---- CTA ---- */
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

        /* ---- Link ---- */
        .access-link {
            padding: 0 24px 20px;
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
            word-break: break-all;
        }

        .access-link a {
            color: #1a73e8;
            text-decoration: none;
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

        {{-- Header --}}
        <div class="header">

            {{-- @if (!empty($logoUrl))
            <img class="logo" src="{{ $logoUrl }}" alt="{{ $instituicao->nome }}">
            @endif --}}

        </div>

        <hr class="divider">

        {{-- Greeting --}}
        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            A defesa do vosso grupo PAP foi agendada. Consulte os detalhes abaixo.
        </p>

        {{-- Detalhes da defesa --}}
        <div class="info-card">

            <div class="card-row">
                <div class="card-label">Grupo</div>
                <div class="card-value">{{ $nomeGrupo }}</div>
            </div>

            <div class="card-row">
                <div class="card-label">Data</div>
                <div class="card-value">{{ $dataDefesa }}</div>
            </div>

            @if (!empty($localDefesa))
                <div class="card-row">
                    <div class="card-label">Local</div>
                    <div class="card-value">{{ $localDefesa }}</div>
                </div>
            @endif

        </div>

        {{-- CTA --}}
        <div class="cta-wrapper" style="margin-top: 24px;">

            <a href="{{ $url }}" class="cta-button" target="_blank">
                Ver grupo PAP
            </a>

        </div>

        {{-- Access Link --}}
        <p class="access-link">

            Se o botão acima não funcionar, copie e cole o seguinte endereço
            no seu navegador:

            <br><br>

            <a href="{{ $url }}" target="_blank">
                {{ $url }}
            </a>

        </p>

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