<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Tema PAP reenviado para validação</title>
</head>

<body style="margin: 0; padding: 24px 0; background: #f1f3f4; color: #202124; font-family: Arial, sans-serif; font-size: 14px;">
    <div style="max-width: 480px; margin: 0 auto; overflow: hidden; background: #ffffff;">
        @include('mail.partials.institution-logo')

        <div style="padding: 24px;">
            <h1 style="margin: 0 0 16px; font-size: 20px; font-weight: 500;">Tema PAP reenviado</h1>

            <p style="line-height: 1.6;">
                O grupo <strong>{{ $nomeGrupo }}</strong> corrigiu e reenviou o tema para a sua validação.
            </p>

            <div style="margin: 20px 0; padding: 16px; border: 1px solid #dadce0; background: #f8f9fa;">
                <p style="margin: 0 0 6px; color: #5f6368; font-size: 12px;">Tema</p>
                <p style="margin: 0; font-weight: 500;">{{ $temaGrupo }}</p>
            </div>

            <p style="margin: 24px 0; text-align: center;">
                <a href="{{ $url }}" style="display: inline-block; padding: 10px 24px; background: #1a73e8; color: #ffffff; text-decoration: none;">
                    Rever tema PAP
                </a>
            </p>

            <hr style="border: 0; border-top: 1px solid #e8eaed;">
            <p style="margin: 16px 0 0; color: #5f6368; font-size: 11px; line-height: 1.6; text-align: center;">
                Este email foi enviado automaticamente pela plataforma {{ config('app.name') }}.
            </p>
        </div>
    </div>
</body>

</html>