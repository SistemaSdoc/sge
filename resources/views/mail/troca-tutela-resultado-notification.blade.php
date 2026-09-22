@php
    $title = $resultado === 'pendente'
        ? 'Troca de tutela pendente'
        : ($fase === 'instituicao_anterior'
            ? 'Troca de tutela aprovada'
            : ($resultado === 'aprovada' ? 'Tutela aceite' : 'Tutela rejeitada'));
@endphp
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>

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

        .divider {
            border: none;
            border-top: 1px solid #e8eaed;
            margin: 0 40px;
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

        <div class="header"></div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá!
        </p>

        @if ($resultado === 'pendente')
            <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
                A troca de tutela do curso <strong>{{ $nomeCurso }}</strong> para o
                <strong>{{ $nomeInstituicaoProposta }}</strong> foi registada.
                O pedido aguarda a aprovação da instituição tutora actual,
                <strong>{{ $nomeInstituicaoDecisora }}</strong>.
            </p>
        @elseif ($fase === 'instituicao_anterior')
            <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
                A instituição tutora actual, <strong>{{ $nomeInstituicaoDecisora }}</strong>, aprovou a troca
                de tutela do curso <strong>{{ $nomeCurso }}</strong> para o
                <strong>{{ $nomeInstituicaoProposta }}</strong>.
                A instituição proposta ainda precisa aceitar a tutela para concluir a troca.
            </p>
        @elseif ($resultado === 'aprovada')
            <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
                O <strong>{{ $nomeInstituicaoDecisora }}</strong> aceitou assumir a tutela do curso
                <strong>{{ $nomeCurso }}</strong>. A troca de tutela foi concluída.
            </p>
        @else
            <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
                O <strong>{{ $nomeInstituicaoDecisora }}</strong> rejeitou assumir a tutela do curso
                <strong>{{ $nomeCurso }}</strong>. A tutela anterior permanece activa.
            </p>
        @endif

        <div class="cta-wrapper">
            <a href="{{ $url }}" class="cta-button" target="_blank">Ver detalhes</a>
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