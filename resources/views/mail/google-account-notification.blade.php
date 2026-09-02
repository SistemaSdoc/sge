{{-- resources/views/emails/account-notification.blade.php --}}
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notificação da sua conta' }}</title>
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

        /* ---- Header ---- */
        .header {
            text-align: center;
            padding: 32px 40px 24px;
        }

        .header img.logo {
            width: 75px;
            height: 24px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 400;
            color: #202124;
            line-height: 1.4;
        }

        .header .account-badge {
            display: inline-flex;
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

        /* ---- Illustration Area ---- */
        .illustration {
            text-align: center;
            padding: 28px 40px 16px;
        }

        .illustration img {
            width: 120px;
            height: auto;
        }

        /* ---- Info Banner ---- */
        .info-banner {
            margin: 0 24px 20px;
            background: #e8f0fe;
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .info-banner .icon {
            color: #1a73e8;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .info-banner p {
            font-size: 13px;
            color: #202124;
            line-height: 1.5;
        }

        .info-banner p strong {
            font-weight: 500;
        }

        .info-banner .note {
            margin-top: 8px;
            font-size: 13px;
            color: #3c4043;
        }

        /* ---- Profile Section ---- */
        .section-label {
            padding: 0 24px 12px;
            font-size: 13px;
            color: #202124;
        }

        .profile-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 24px;
        }

        .profile-item .item-icon {
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

        .profile-item .item-text .label {
            font-size: 14px;
            color: #202124;
        }

        .profile-item .item-text .sublabel {
            font-size: 12px;
            color: #5f6368;
            margin-top: 2px;
        }

        /* ---- Timestamp ---- */
        .timestamp {
            padding: 16px 24px;
            font-size: 13px;
            color: #202124;
        }

        .revoke-link {
            padding: 8px 24px 20px;
            font-size: 13px;
            color: #202124;
        }

        /* ---- CTA Button ---- */
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

        /* ---- Security Footer ---- */
        .security-note {
            margin: 0 24px 20px;
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

        .footer a {
            color: #1a73e8;
            text-decoration: none;
        }

        .footer .company {
            margin-top: 8px;
            font-size: 11px;
            color: #80868b;
        }
    </style>
</head>
<body>
<div class="email-wrapper">

    {{-- Header --}}
    <div class="header">
        {{-- Substitui pela logo da tua aplicação --}}
        <img
            class="logo"
            src="{{ $logoUrl ?? asset('images/logo.png') }}"
            alt="{{ config('app.name') }}"
        >

        <h1>{{ $title ?? 'Acompanhe os dados da sua Conta' }}</h1>

        <div class="account-badge">
            <span class="avatar">{{ strtoupper(substr($userName ?? 'U', 0, 1)) }}</span>
            {{ $userEmail }}
        </div>
    </div>

    <hr class="divider">

    {{-- Illustration (opcional) --}}
    @if (!empty($illustrationUrl))
    <div class="illustration">
        <img src="{{ $illustrationUrl }}" alt="">
    </div>
    @endif

    {{-- Info Banner --}}
    <div class="info-banner">
        <span class="icon">ℹ</span>
        <div>
            <p>
                Recebeu este email porque usou a funcionalidade
                <strong>{{ $featureName ?? 'Iniciar sessão' }}</strong> com a
                {{ config('app.name') }} a
                <strong>{{ \Carbon\Carbon::parse($actionAt)->format('d \d\e F \à\s H:i') }}</strong>.
            </p>
            @if (!empty($emailNote))
            <p class="note">{{ $emailNote }}</p>
            @else
            <p class="note">Este email resume as informações que partilhou. Neste momento, não precisa de fazer nada.</p>
            @endif
        </div>
    </div>

    {{-- Profile Data --}}
    <p class="section-label">A app {{ config('app.name') }} recebeu esta informação do perfil</p>

    @if (!empty($userName))
    <div class="profile-item">
        <div class="item-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <div class="item-text">
            <div class="label">{{ $userName }}</div>
            <div class="sublabel">Nome e imagem do perfil</div>
        </div>
    </div>
    @endif

    @if (!empty($userEmail))
    <div class="profile-item">
        <div class="item-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
        </div>
        <div class="item-text">
            <div class="label">{{ $userEmail }}</div>
            <div class="sublabel">Endereço de email</div>
        </div>
    </div>
    @endif

    {{-- Campos extra (opcional) --}}
    @foreach ($extraFields ?? [] as $field)
    <div class="profile-item">
        <div class="item-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
        </div>
        <div class="item-text">
            <div class="label">{{ $field['value'] }}</div>
            <div class="sublabel">{{ $field['label'] }}</div>
        </div>
    </div>
    @endforeach

    {{-- Timestamp --}}
    <p class="timestamp">
        Este email inclui as informações que partilhou a
        <strong>{{ \Carbon\Carbon::parse($actionAt)->format('d \d\e F \à\s H:i') }}</strong>.
    </p>

    {{-- Revoke text --}}
    <p class="revoke-link">
        Se quiser deixar de usar a funcionalidade
        {{ $featureName ?? 'Iniciar sessão com Google' }} com a app
        {{ config('app.name') }}, aceda à sua Conta.
    </p>

    {{-- CTA --}}
    <div class="cta-wrapper">
        <a href="{{ $ctaUrl ?? url('/') }}" class="cta-button">
            {{ $ctaLabel ?? 'Aceder à sua Conta' }}
        </a>
    </div>

    {{-- Política --}}
    @if (!empty($privacyUrl))
    <p style="padding: 0 24px 16px; font-size: 12px; color: #5f6368; line-height: 1.5;">
        Reveja a <a href="{{ $privacyUrl }}" style="color:#1a73e8;">Política de Privacidade</a>
        e os Termos de Utilização da app {{ config('app.name') }} para compreender como vamos
        tratar e proteger os seus dados.
    </p>
    @endif

    {{-- Security Box --}}
    <div class="security-note">
        <span class="shield-icon">🛡</span>
        <div class="text">
            <div class="title">Maior segurança com {{ config('app.name') }}</div>
            <p>A sua Conta protege a sua privacidade com segurança avançada concebida para manter os seus dados seguros.</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>
            Recebeu este email para ficar a par de alterações importantes aos serviços e à sua Conta.<br>
            Se quiser deixar de receber estes emails, pode
            <a href="{{ $unsubscribeUrl ?? '#' }}">anular a subscrição</a>.<br>
            Mesmo que anule a subscrição destes emails, vai continuar a receber emails de segurança.
        </p>
        <p class="company">
            © {{ date('Y') }} {{ config('app.name') }}
            @if (!empty($companyAddress))
                &nbsp;·&nbsp; {{ $companyAddress }}
            @endif
        </p>
    </div>

</div>
</body>
</html>