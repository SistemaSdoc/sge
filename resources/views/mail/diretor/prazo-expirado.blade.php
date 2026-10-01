<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Prazo expirado</title>
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

        /* Barra de progresso */
        .progress-block {
            margin: 16px 24px;
            padding: 18px 20px;
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            text-align: center;
        }

        .progress-block .label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .progress-block .percent {
            font-size: 32px;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 12px;
        }

        .progress-block .percent.ok    { color: #137333; }
        .progress-block .percent.warn  { color: #b06000; }
        .progress-block .percent.danger{ color: #b3261e; }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e8eaed;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar .fill {
            height: 100%;
            border-radius: 4px;
        }

        .progress-bar .fill.ok     { background: #137333; }
        .progress-bar .fill.warn   { background: #f9ab00; }
        .progress-bar .fill.danger { background: #b3261e; }

        /* Grid de estatísticas */
        .stats-grid {
            margin: 8px 24px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .stat-card {
            flex: 1 1 calc(50% - 4px);
            min-width: 130px;
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .stat-card .stat-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 6px;
        }

        .stat-card .stat-value {
            font-size: 22px;
            font-weight: 500;
            color: #202124;
            line-height: 1;
        }

        .stat-card.ok     .stat-value { color: #137333; }
        .stat-card.warn   .stat-value { color: #b06000; }
        .stat-card.danger .stat-value { color: #b3261e; }

        .security-note {
            margin: 20px 24px;
            background: #fce8e6;
            border: 1px solid #f4c7c3;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .security-note .text .title {
            font-size: 13px;
            font-weight: 500;
            color: #b3261e;
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
            .stat-card { flex: 1 1 100%; }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">

        <div class="header">
            <h1>Prazo expirado</h1>
            <div class="account-badge">
                {{ $instituicao->nome ?? 'SGE' }}
            </div>
        </div>

        <hr class="divider">

        <br>
        <p style="padding: 0 24px; font-size: 14px; line-height: 1.6; color: #202124;">
            Olá, <strong>{{ $nome }}</strong>!
        </p>

        <p style="padding: 12px 24px 20px; font-size: 14px; line-height: 1.6; color: #202124;">
            O prazo <strong>{{ $titulo }}</strong> expirou. Segue o resumo de cumprimento dos professores.
        </p>

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

        <br>

        {{-- Barra de progresso --}}
        @php
            $classe = $taxaCumprimento >= 80 ? 'ok' : ($taxaCumprimento >= 50 ? 'warn' : 'danger');
        @endphp

        <div class="progress-block">
            <div class="label">Taxa de cumprimento</div>
            <div class="percent {{ $classe }}">{{ $taxaCumprimento }}%</div>
            <div class="progress-bar">
                <div class="fill {{ $classe }}" style="width: {{ $taxaCumprimento }}%;"></div>
            </div>
        </div>

        <p class="section-label">Estatísticas</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de professores</div>
                <div class="stat-value">{{ $totalProfessores }}</div>
            </div>

            <div class="stat-card ok">
                <div class="stat-label">Submeteram</div>
                <div class="stat-value">{{ $submeteram }}</div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Não submeteram</div>
                <div class="stat-value">{{ $naoSubmeteram }}</div>
            </div>

            <div class="stat-card warn">
                <div class="stat-label">Justificaram</div>
                <div class="stat-value">{{ $justificaram }}</div>
            </div>
        </div>

        @if($naoSubmeteram > 0)
            <div class="security-note">
                <div class="text">
                    <div class="title">Professores em falta</div>
                    <p>Existem {{ $naoSubmeteram }} professor(es) que não submeteram a prova.
                        Consulte a lista detalhada no painel para tomar as devidas providências.</p>
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