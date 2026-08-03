<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 30px 35px; }
  body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; }

  /* Cabeçalho da instituição */
  .header-instituicao {
    display: table;
    width: 100%;
    margin-bottom: 12px;
    border-bottom: 2px solid #1e3a8a;
    padding-bottom: 8px;
  }
  .header-instituicao .logo-cell {
    display: table-cell;
    vertical-align: middle;
    width: 80px;
    padding-right: 12px;
  }
  .header-instituicao .info-cell {
    display: table-cell;
    vertical-align: middle;
  }
  .header-instituicao .info-cell .nome {
    font-size: 18px;
    font-weight: bold;
    color: #1e3a8a;
    margin: 0;
  }
  .header-instituicao .info-cell .detalhes {
    font-size: 10px;
    color: #4b5563;
    margin: 2px 0 0 0;
  }
  .header-instituicao .data-cell {
    display: table-cell;
    vertical-align: bottom;
    text-align: right;
    font-size: 10px;
    color: #4b5563;
  }

  /* Título do relatório e turma */
  .titulo-relatorio {
    font-size: 16px;
    font-weight: bold;
    color: #1e3a8a;
    margin: 8px 0 2px 0;
  }
  .subtitulo-relatorio {
    font-size: 12px;
    color: #4b5563;
    margin: 0 0 12px 0;
  }

  .info-turma {
    display: table;
    width: 100%;
    margin-bottom: 16px;
    background: #f3f4f6;
    padding: 8px 10px;
  }
  .info-item {
    display: table-cell;
    padding: 0 8px;
  }
  .info-label {
    font-size: 8.5px;
    text-transform: uppercase;
    color: #6b7280;
  }
  .info-valor {
    font-size: 11px;
    font-weight: bold;
  }

  .secao-titulo {
    font-size: 12px;
    font-weight: bold;
    color: #1e3a8a;
    margin: 18px 0 6px 0;
  }

  table.dados {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  table.dados th {
    background: #1e3a8a;
    color: #fff;
    text-align: left;
    padding: 6px 8px;
    font-size: 10px;
  }
  table.dados td {
    padding: 6px 8px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
  }
  table.dados tr:nth-child(even) td {
    background: #f9fafb;
  }

  table.dados.em-dia th {
    background: #15803d;
  }

  .badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 9px;
    color: #fff;
  }
  .badge-alerta { background: #dc2626; }
  .badge-atencao { background: #d97706; }
  .badge-leve { background: #ca8a04; }
  .badge-em-dia { background: #15803d; }

  .meses-lista {
    font-size: 9.5px;
    color: #4b5563;
  }

  .resumo {
    display: table;
    width: 100%;
    margin-top: 10px;
    border-top: 2px solid #1e3a8a;
    padding-top: 10px;
  }
  .resumo-item {
    display: table-cell;
    text-align: center;
  }
  .resumo-num {
    font-size: 16px;
    font-weight: bold;
    color: #1e3a8a;
  }
  .resumo-label {
    font-size: 9px;
    color: #6b7280;
  }

  .rodape {
    margin-top: 24px;
    font-size: 8.5px;
    color: #9ca3af;
    text-align: center;
  }
</style>
</head>
<body>

  <!-- Cabeçalho da instituição -->
  <div class="header-instituicao">
    <div class="logo-cell">
      @if($instituicao && $instituicao['logo_base64'])
        <img src="{{ $instituicao['logo_base64'] }}" height="60" alt="Logo">
      @endif
    </div>
    <div class="info-cell">
      <p class="nome">{{ $instituicao['nome'] ?? 'Instituição' }}</p>
      <p class="detalhes">
        {{ $instituicao['endereco'] ?? '' }}
        @if($instituicao['provincia'] ?? false) - {{ $instituicao['provincia'] }} @endif
      </p>
      <p class="detalhes">
        @if($instituicao['telefone'] ?? false) Tel: {{ $instituicao['telefone'] }} @endif
        @if($instituicao['email'] ?? false) | Email: {{ $instituicao['email'] }} @endif
      </p>
    </div>
    <div class="data-cell">
      <p>Emitido em: {{ $geradoEm }}</p>
      <p>Ano lectivo: {{ $turma['ano_lectivo'] }}</p>
    </div>
  </div>

  <!-- Título do relatório -->
  <p class="titulo-relatorio">Relatório de Situação de Propinas</p>
  <p class="subtitulo-relatorio">
    {{ $turma['curso'] ?? '' }} — {{ $turma['classe'] ?? '' }} — Turma {{ $turma['nome'] ?? '' }}
    @if($turma['turno'] ?? false) ({{ $turma['turno'] }}) @endif
  </p>

  <!-- Bloco de resumo -->
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
      <div class="info-label">Alunos em atraso</div>
      <div class="info-valor">{{ $resumo['total_devedores'] }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Alunos em dia</div>
      <div class="info-valor">{{ $resumo['total_em_dia'] }}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Valor total em dívida</div>
      <div class="info-valor">{{ number_format($resumo['valor_total_geral'], 2, ',', '.') }} AOA</div>
    </div>
  </div>

  <p class="secao-titulo">Alunos em atraso</p>

  @if(count($linhas) === 0)
    <p style="text-align:center; color:#6b7280; padding: 20px 0;">Nenhum aluno desta turma está em atraso.</p>
  @else
    <table class="dados">
      <thead>
        <tr>
          <th style="width: 30px;">#</th>
          <th>Aluno</th>
          <th style="width: 70px; text-align:center;">Meses em falta</th>
          <th style="width: 230px;">Meses</th>
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
              <span class="badge {{ $badge }}">{{ $linha['total_meses'] }}</span>
            </td>
            <td class="meses-lista">
              {{ collect($linha['meses'])->pluck('label')->implode(', ') }}
            </td>
            <td style="text-align:right; font-weight:bold;">
              {{ number_format($linha['valor_total'], 2, ',', '.') }} AOA
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="secao-titulo">Alunos em dia</p>

  @if(count($emDia) === 0)
    <p style="text-align:center; color:#6b7280; padding: 20px 0;">Nenhum aluno desta turma está em dia.</p>
  @else
    <table class="dados em-dia">
      <thead>
        <tr>
          <th style="width: 30px;">#</th>
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
              <span class="badge badge-em-dia">Em dia</span>
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
      <div class="resumo-label">ALUNOS NA TURMA</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ $resumo['total_devedores'] }}</div>
      <div class="resumo-label">EM ATRASO</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ $resumo['total_em_dia'] }}</div>
      <div class="resumo-label">EM DIA</div>
    </div>
    <div class="resumo-item">
      <div class="resumo-num">{{ number_format($resumo['valor_total_geral'], 0, ',', '.') }} AOA</div>
      <div class="resumo-label">TOTAL EM DÍVIDA</div>
    </div>
  </div>

  <p class="rodape">Documento gerado automaticamente pelo sistema de gestão escolar.</p>

</body>
</html>