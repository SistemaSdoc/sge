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

        /* ─── Tabela de atribuições ─── */
        .atribuicoes-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 20px;
            font-size: 13px;
        }

        .atribuicoes-table thead th {
            background: #f1f3f4;
            padding: 10px 16px;
            text-align: left;
            font-weight: 500;
            font-size: 11px;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #e8eaed;
        }

        .atribuicoes-table tbody td {
            padding: 10px 16px;
            border-bottom: 1px solid #f1f3f4;
            color: #202124;
            vertical-align: middle;
        }

        .atribuicoes-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .atribuicoes-table tbody tr.row-pending {
            background: #fffaf0;
        }

        .atribuicoes-table .col-prof {
            font-weight: 500;
        }

        .atribuicoes-table .col-turma {
            color: #1a73e8;
            font-weight: 500;
        }

        .badge-estado {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-estado.ok {
            background: #e6f4ea;
            color: #137333;
        }

        .badge-estado.warn {
            background: #fef7e0;
            color: #b06000;
        }

        .badge-estado.danger {
            background: #fce8e6;
            color: #b3261e;
        }

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
            .atribuicoes-table { font-size: 12px; }
            .atribuicoes-table thead th,
            .atribuicoes-table tbody td { padding: 8px 10px; }
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

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- DETALHES DO PRAZO                                    --}}
        {{-- ════════════════════════════════════════════════════ --}}
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

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- BARRA DE PROGRESSO                                   --}}
        {{-- ════════════════════════════════════════════════════ --}}
        @php
            $classeProgresso = $taxaCumprimento >= 80 ? 'ok' : ($taxaCumprimento >= 50 ? 'warn' : 'danger');
        @endphp

        <div class="progress-block">
            <div class="label">Taxa de cumprimento</div>
            <div class="percent {{ $classeProgresso }}">{{ $taxaCumprimento }}%</div>
            <div class="progress-bar">
                <div class="fill {{ $classeProgresso }}" style="width: {{ $taxaCumprimento }}%;"></div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ESTATÍSTICAS                                         --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <p class="section-label">Estatísticas</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de atribuições</div>
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

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- DETALHES POR PROFESSOR + TURMA                       --}}
        {{-- ════════════════════════════════════════════════════ --}}
        @if(!empty($atribuicoes))
            <p class="section-label">Detalhes por professor</p>

            <table class="atribuicoes-table">
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Turma</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($atribuicoes as $atr)
                        @php
                            $submeteu = $atr['submeteu'] ?? false;
                            $justStatus = $atr['justificativa_status'] ?? null;

                            if ($submeteu) {
                                $estadoLabel = 'Submeteu';
                                $estadoClass = 'ok';
                            } elseif ($justStatus === 'aceita') {
                                $estadoLabel = 'Justificou (aceite)';
                                $estadoClass = 'warn';
                            } elseif ($justStatus === 'pendente') {
                                $estadoLabel = 'Justificou (pendente)';
                                $estadoClass = 'warn';
                            } elseif ($justStatus === 'recusada') {
                                $estadoLabel = 'Justificativa recusada';
                                $estadoClass = 'danger';
                            } else {
                                $estadoLabel = 'Não submeteu';
                                $estadoClass = 'danger';
                            }
                        @endphp

                        <tr class="{{ $submeteu ? '' : 'row-pending' }}">
                            <td class="col-prof">{{ $atr['professor_nome'] ?? '—' }}</td>
                            <td class="col-turma">{{ $atr['turma_nome'] ?? '—' }}</td>
                            <td>
                                <span class="badge-estado {{ $estadoClass }}">
                                    {{ $estadoLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- AVISO                                                --}}
        {{-- ════════════════════════════════════════════════════ --}}
        @if($naoSubmeteram > 0)
            <div class="security-note">
                <div class="text">
                    <div class="title">Ação recomendada</div>
                    <p>
                        Existem <strong>{{ $naoSubmeteram }}</strong> atribuição(ões) sem submissão.
                        Consulte a lista detalhada no painel para tomar as devidas providências.
                    </p>
                </div>
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- FOOTER                                               --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="footer">
            <p>
                Este email foi enviado automaticamente pela plataforma
                {{ $instituicao->nome ?? config('app.name') }}.
                Por favor, não responda directamente a esta mensagem.
            </p>
            <p class="company">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>

    </div>
</body>

</html>