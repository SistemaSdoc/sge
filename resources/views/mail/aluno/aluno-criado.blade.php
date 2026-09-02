<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">

    <title>Conta criada com sucesso</title>

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

        .header .account-badge .avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #4285f4;
            color: #fff;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        /* ---- Divider ---- */
        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
        }

        /* ---- Success Banner ---- */
        .success-banner {
            margin: 24px 24px 20px;
            background: #e6f4ea;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .success-banner .icon {
            color: #188038;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .success-banner p {
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
        }

        .success-banner p strong {
            font-weight: 500;
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

        .security-note .shield-icon {
            color: #1a73e8;
            font-size: 20px;
            flex-shrink: 0;
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

        {{-- Success Banner --}}

        {{-- Greeting --}}
        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            A sua matrícula {{ $artigoInstituicao }} <strong>{{ $instituicao->nome }}</strong> foi aprovada com sucesso.
            A sua conta está pronta, utilize as credenciais abaixo para aceder à plataforma.
        </p>

        {{-- Credentials --}}
        <p class="section-label">
            Dados de acesso à sua conta
        </p>

        @if (!empty($email))
            <div class="credential-item">

                <div class="item-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                    </svg>
                </div>

                <div class="item-text">
                    <div class="label">{{ $email }}</div>
                    <div class="sublabel">Email de acesso</div>
                </div>

            </div>
        @endif

        @if (!empty($password))
            <div class="credential-item">

                <div class="item-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6-5c1.66 0 3 1.34 3 3v2H9V6c0-1.66 1.34-3 3-3zm5 17H7V10h10v10zm-5-3c.83 0 1.5-.67 1.5-1.5S12.83 14 12 14s-1.5.67-1.5 1.5S11.17 17 12 17z" />
                    </svg>
                </div>

                <div class="item-text">
                    <div class="label">{{ $password }}</div>
                    <div class="sublabel">Password de acesso</div>
                </div>

            </div>
        @endif

        {{-- Password Recommendation --}}
        <div class="security-note">

            <div class="text">

                <div class="title">
                    Proteja a sua conta
                </div>

                <p>
                    Por motivos de segurança, recomendamos que altere a sua
                    password após realizar o primeiro acesso e que não partilhe
                    as suas credenciais com terceiros.
                </p>

            </div>

        </div>

        {{-- CTA --}}
        <div class="cta-wrapper">

            <a href="{{ $url }}" class="cta-button" target="_blank">
                Aceder à plataforma
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