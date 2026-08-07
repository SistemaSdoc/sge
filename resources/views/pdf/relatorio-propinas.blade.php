<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <style>
    @page {
      margin: 32px 38px;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Times New Roman', 'Georgia', serif;
      font-size: 11px;
      color: #2b2b2b;
      line-height: 1.4;
    }

    /* ===== Cabeçalho institucional ===== */
    .header-instituicao {
      display: table;
      width: 100%;
      margin-bottom: 14px;
      border-bottom: 1.5px solid #2b3a4a;
      padding-bottom: 10px;
    }

    .header-instituicao .logo-cell {
      display: table-cell;
      vertical-align: middle;
      width: 70px;
      padding-right: 14px;
    }

    .header-instituicao .info-cell {
      display: table-cell;
      vertical-align: middle;
    }

    .header-instituicao .info-cell .nome {
      font-size: 16px;
      font-weight: bold;
      color: #1c2733;
      margin: 0;
      letter-spacing: 0.3px;
    }

    .header-instituicao .info-cell .detalhes {
      font-size: 9.5px;
      color: #5a5a5a;
      margin: 2px 0 0 0;
      font-family: 'Helvetica', sans-serif;
    }

    .header-instituicao .data-cell {
      display: table-cell;
      vertical-align: bottom;
      text-align: right;
      font-size: 9.5px;
      color: #5a5a5a;
      font-family: 'Helvetica', sans-serif;
    }

    .header-instituicao .data-cell p {
      margin: 1px 0;
    }

    /* ===== Título do relatório ===== */
    .titulo-relatorio {
      font-size: 15px;
      font-weight: bold;
      color: #1c2733;
      margin: 10px 0 2px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .subtitulo-relatorio {
      font-size: 11px;
      color: #5a5a5a;
      margin: 0 0 14px 0;
      font-family: 'Helvetica', sans-serif;
    }

    /* ===== Bloco de info da turma ===== */
    .info-turma {
      display: table;
      width: 100%;
      margin-bottom: 18px;
      border: 1px solid #d8d8d8;
      background: #fafafa;
      padding: 9px 4px;
    }

    .info-item {
      display: table-cell;
      padding: 0 12px;
      border-right: 1px solid #e2e2e2;
    }

    .info-item:last-child {
      border-right: none;
    }

    .info-label {
      font-size: 8px;
      text-transform: uppercase;
      color: #8a8a8a;
      font-family: 'Helvetica', sans-serif;
      letter-spacing: 0.4px;
    }

    .info-valor {
      font-size: 11px;
      font-weight: bold;
      color: #1c2733;
      margin-top: 2px;
      font-family: 'Helvetica', sans-serif;
    }

    /* ===== Secções ===== */
    .secao-titulo {
      font-size: 11.5px;
      font-weight: bold;
      color: #1c2733;
      margin: 20px 0 7px 0;
      padding-bottom: 4px;
      border-bottom: 1px solid #c9c9c9;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      font-family: 'Helvetica', sans-serif;
    }

    /* ===== Tabelas ===== */
    table.dados {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
      font-family: 'Helvetica', sans-serif;
    }

    table.dados th {
      background: #2b3a4a;
      color: #f2f2f2;
      text-align: left;
      padding: 6px 8px;
      font-size: 9px;
      font-weight: normal;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    table.dados td {
      padding: 6px 8px;
      border-bottom: 1px solid #e5e5e5;
      vertical-align: top;
      font-size: 10px;
    }

    table.dados tr:nth-child(even) td {
      background: #f7f7f7;
    }

    table.dados.em-dia th {
      background: #4a5a4a;
    }

    /* ===== Indicadores de estado — tons contidos ===== */
    .badge {
      display: inline-block;
      padding: 1.5px 7px;
      border-radius: 2px;
      font-size: 8.5px;
      font-family: 'Helvetica', sans-serif;
      border: 1px solid transparent;
    }

    .badge-alerta {
      background: #f3e3e3;
      color: #7a2e2e;
      border-color: #d9b8b8;
    }

    .badge-atencao {
      background: #f2ead8;
      color: #7a5c1e;
      border-color: #ddc99a;
    }

    .badge-leve {
      background: #f0eed8;
      color: #6b6320;
      border-color: #d9d19a;
    }

    .meses-lista {
      font-size: 9px;
      color: #5a5a5a;
      font-family: 'Helvetica', sans-serif;
    }

    /* ===== Resumo final ===== */
    .resumo {
      display: table;
      width: 100%;
      margin-top: 14px;
      border-top: 1.5px solid #2b3a4a;
      padding-top: 12px;
    }

    .resumo-item {
      display: table-cell;
      text-align: center;
      border-right: 1px solid #e2e2e2;
    }

    .resumo-item:last-child {
      border-right: none;
    }

    .resumo-num {
      font-size: 15px;
      font-weight: bold;
      color: #1c2733;
      font-family: 'Helvetica', sans-serif;
    }

    .resumo-label {
      font-size: 8px;
      color: #8a8a8a;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-top: 2px;
      font-family: 'Helvetica', sans-serif;
    }

    /* ===== Rodapé ===== */
    .rodape {
      margin-top: 28px;
      font-size: 8px;
      color: #a0a0a0;
      text-align: center;
      font-family: 'Helvetica', sans-serif;
      border-top: 1px solid #ececec;
      padding-top: 8px;
    }

    .sem-registos {
      text-align: center;
      color: #8a8a8a;
      padding: 18px 0;
      font-style: italic;
      font-size: 10px;
    }
  </style>
</head>

<body>

  <!-- Cabeçalho da instituição -->
  <div class="header-instituicao">
    <div class="logo-cell">
      @if($instituicao && $instituicao['logo_base64'])
        <img src="{{ $instituicao['logo_base64'] }}" height="52" alt="Logo">
      @endif
    </div>
    <div class="info-cell">
      <p class="nome">{{ $instituicao['nome'] ?? 'Instituição' }}</p>
      <p class="detalhes">
        {{ $instituicao['endereco'] ?? '' }}
        @if($instituicao['provincia'] ?? false) — {{ $instituicao['provincia'] }} @endif
      </p>
      <p class="detalhes">
        @if($instituicao['telefone'] ?? false) Tel: {{ $instituicao['telefone'] }} @endif
        @if($instituicao['email'] ?? false) &nbsp;|&nbsp; Email: {{ $instituicao['email'] }} @endif
      </p>
    </div>
    <div class="data-cell">
      <p>Emitido em {{ $geradoEm }}</p>
      <p>Ano lectivo {{ $turma['ano_lectivo'] }}</p>
    </div>
  </div>

  <!-- Título do relatório -->
  <p class="titulo-relatorio">Relatório de Situação de Propinas por Turma</p>
  <p class="subtitulo-relatorio">
    {{ $turma['curso'] ?? '' }} — {{ $turma['classe'] ?? '' }} — Turma {{ $turma['nome'] ?? '' }}
    @if($turma['turno'] ?? false) ({{ $turma['turno'] }}) @endif
  </p>

  <!-- Bloco de resumo da turma -->
  <div class="info-turma">
    <div class="info-item">
      <div class="info-label">Turno</div>
      <div class="info-valor">{{ $turma['turno'] ?? '—' }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Total de alunos</div>
      <div class="info-valor">{{ $resumo['total_alunos'] }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Alunos com situação financeira não regularizada</div>
      <div class="info-valor">{{ $resumo['total_devedores'] }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Alunos Reguralizado(s)</div>
      <div class="info-valor">{{ $resumo['total_em_dia'] }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Total em multas</div>
      <div class="info-valor">{{ number_format($resumo['multa_total_geral'], 2, ',', '.') }} AOA</div>
    </div>
    <div class="info-item">
      <div class="info-label">Valor total em dívida</div>
      <div class="info-valor">{{ number_format($resumo['valor_total_geral'], 2, ',', '.') }} AOA</div>
    </div>
  </div>

  <p class="secao-titulo">Alunos com situação financeira não regularizada</p>

  @if(count($linhas) === 0)
    <p class="sem-registos">Nenhum aluno desta turma está em atraso.</p>
  @else
    <table class="dados">
      <thead>
        <tr>
          <th style="width: 24px;">#</th>
          <th>Aluno</th>
          <th style="width: 60px; text-align:center;">Meses em falta</th>
          <th style="width: 190px;">Meses</th>
          <th style="width: 75px; text-align:right;">Multa</th>
          <th style="width: 90px; text-align:right;">Valor devido</th>
        </tr>
      </thead>
      <tbody>
        @foreach($linhas as $i => $linha)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $linha['nome'] }}</td>
            <td style="text-align:center;">
              @php
                $badge = $linha['total_meses'] >= 3 ? 'badge-alerta' : ($linha['total_meses'] === 2 ? 'badge-atencao' : 'badge-leve');
              @endphp
              <span >{{ $linha['total_meses'] }}</span>
            </td>
            <td class="meses-lista">
              {{ collect($linha['meses'])->pluck('label')->implode(', ') }}
            </td>
            <td style="text-align:right;">
              @if($linha['multa_total'] > 0)
                <span style="color:#7a2e2e; font-weight:bold;">
                  {{ number_format($linha['multa_total'], 2, ',', '.') }}
                </span>
              @else
                —
              @endif
            </td>
            <td style="text-align:right; font-weight:bold;">
              {{ number_format($linha['valor_total'], 2, ',', '.') }} AOA
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="secao-titulo">Alunos Reguralizado(s)</p>

  @if(count($emDia) === 0)
    <p class="sem-registos">Nenhum aluno desta turma está Reguralizado.</p>
  @else
    <table class="dados em-dia">
      <thead>
        <tr>
          <th style="width: 28px;">#</th>
          <th>Aluno</th>
          <th style="width: 100px; text-align:center;">Situação</th>
        </tr>
      </thead>
      <tbody>
        @foreach($emDia as $i => $aluno)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $aluno['nome'] }}</td>
            <td style="text-align:center;">
              <span>Reguralizada</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- Resumo final -->
  <div class="resumo">
    <div class="resumo-item">
      <div class="resumo-num">{{ $resumo['total_alunos'] }}</div>
      <div class="resumo-label">Alunos na Turma</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ $resumo['total_devedores'] }}</div>
      <div class="resumo-label">Não Regularizada</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ $resumo['total_em_dia'] }}</div>
      <div class="resumo-label">Reguralizado(s)</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ number_format($resumo['multa_total_geral'], 0, ',', '.') }} AOA</div>
      <div class="resumo-label">Total em Multas</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ number_format($resumo['valor_total_geral'], 0, ',', '.') }} AOA</div>
      <div class="resumo-label">Total em Dívida</div>
    </div>
  </div>

  <p class="rodape">Documento gerado automaticamente pelo sistema de gestão escolar.</p>

</body>

</html>