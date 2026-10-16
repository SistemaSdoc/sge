@php
    $config = match ($tipo) {
        'criado' => [
            'titulo'    => 'Novo prazo de prova',
            'subtitulo' => 'Foi criado um novo prazo para submissão de provas.',
            'badge'     => 'Novo',
            'cor'       => '#1a73e8',
            'bg'        => '#e8f0fe',
            'border'    => '#c5d4f5',
            'avisoTitulo' => 'Prazo disponível',
            'avisoTexto'  => 'Pode submeter a sua prova a qualquer momento dentro do prazo indicado acima.',
        ],
        'prorrogado' => [
            'titulo'    => 'Prazo prorrogado',
            'subtitulo' => 'O prazo foi prorrogado. Tem mais tempo para submeter a sua prova.',
            'badge'     => 'Prorrogado',
            'cor'       => '#1a73e8',
            'bg'        => '#e8f0fe',
            'border'    => '#c5d4f5',
            'avisoTitulo' => 'Tem mais tempo',
            'avisoTexto'  => 'A data limite foi atualizada. Consulte a nova data acima.',
        ],
        'fechado' => [
            'titulo'    => 'Prazo encerrado',
            'subtitulo' => 'O prazo foi encerrado manualmente pelo diretor.',
            'badge'     => 'Encerrado',
            'cor'       => '#5f6368',
            'bg'        => '#f1f3f4',
            'border'    => '#dadce0',
            'avisoTitulo' => 'Encerrado pelo diretor',
            'avisoTexto'  => 'Este prazo foi encerrado manualmente. Já não aceita novas submissões.',
        ],
        'a_expirar' => [
            'titulo'    => 'Prazo a expirar',
            'subtitulo' => 'Falta menos de 30 minutos para o prazo terminar.',
            'badge'     => 'Urgente',
            'cor'       => '#b06000',
            'bg'        => '#fef7e0',
            'border'    => '#fde293',
            'avisoTitulo' => 'Ação urgente',
            'avisoTexto'  => 'Se ainda não submeteu a sua prova, faça-o agora. O prazo termina em menos de 30 minutos.',
        ],
        'expirado' => [
            'titulo'    => 'Prazo expirado',
            'subtitulo' => 'O prazo terminou. Já não é possível submeter a prova.',
            'badge'     => 'Expirado',
            'cor'       => '#b3261e',
            'bg'        => '#fce8e6',
            'border'    => '#f4c7c3',
            'avisoTitulo' => 'Prazo terminado',
            'avisoTexto'  => 'Já não é possível submeter. Se teve dificuldades, contacte o diretor para avaliar a situação.',
        ],
        default => [
            'titulo'    => 'Atualização de prazo',
            'subtitulo' => 'O prazo foi atualizado.',
            'badge'     => 'Atualizado',
            'cor'       => '#5f6368',
            'bg'        => '#f1f3f4',
            'border'    => '#dadce0',
            'avisoTitulo' => 'Informação',
            'avisoTexto'  => 'Consulte os detalhes atualizados acima.',
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $config['titulo'] }}</title>
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

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin: 4px 24px 20px;
            background: {{ $config['bg'] }};
            color: {{ $config['cor'] }};
            border: 1px solid {{ $config['border'] }};
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

        .security-note {
            margin: 20px 24px;
            background: {{ $config['bg'] }};
            border: 1px solid {{ $config['border'] }};
            border-radius: 8px;
            padding: 14px 16px;
        }

        .security-note .text .title {
            font-size: 13px;
            font-weight: 500;
            color: {{ $config['cor'] }};
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
            <h1>{{ $config['titulo'] }}</h1>
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
            {{ $config['subtitulo'] }}
        </p>

        <div>
            <div class="status-badge">{{ $config['badge'] }}</div>
        </div>

        <p class="section-label">Detalhes do prazo</p>

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Título</div>
                <div class="sublabel">{{ $titulo }}</div>
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

        <div class="credential-item">
            <div class="item-text">
                <div class="label">Data limite</div>
                <div class="sublabel">{{ $dataLimite }}</div>
            </div>
        </div>

        @if(!empty($periodo))
            <div class="credential-item">
                <div class="item-text">
                    <div class="label">Período</div>
                    <div class="sublabel">{{ $periodo }}</div>
                </div>
            </div>
        @endif

        <div class="security-note">
            <div class="text">
                <div class="title">{{ $config['avisoTitulo'] }}</div>
                <p>{{ $config['avisoTexto'] }}</p>
            </div>
        </div>

        <div class="footer">
            <p>Este email foi enviado automaticamente pela plataforma {{ $instituicao->nome }}. Por favor, não responda
                directamente a esta mensagem.</p>
            <p class="company">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>

    </div>
</body>

</html>