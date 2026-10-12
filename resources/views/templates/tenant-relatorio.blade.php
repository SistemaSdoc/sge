<!doctype html>
<html lang="pt">
  <head>
    <meta charset="utf-8" />
    <title>Relatório de Métricas de Usuários</title>
    <style>
      @page {
        size: A4 portrait;
        margin: 0;
      }

      * {
        box-sizing: border-box;
      }

      html,
      body {
        margin: 0;
        padding: 0;
      }

      body {
        color: #23302b;
        font-family: 'Source Sans 3', Helvetica, Arial, sans-serif;
        font-size: 12px;
        line-height: 1.55;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .page {
        width: 794px;
        min-height: 1123px;
        padding: 52px 60px 36px;
        display: flex;
        flex-direction: column;
        background: #fffefb;
      }

      .muted {
        color: #7b8780;
      }

      .accent {
        color: #10115c;
      }

      .rule {
        border-color: #d9dedb;
      }

      .metric-row {
        display: flex;
        padding: 14px 0;
        border-top: 1px solid #d9dedb;
        border-bottom: 1px solid #d9dedb;
      }

      .metric {
        flex: 1;
        padding: 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .metric + .metric {
        border-left: 1px solid #d9dedb;
      }

      .metric-value {
        color: #10115c;
        font-family: Georgia, serif;
        font-size: 30px;
        font-weight: 600;
        line-height: 1;
      }

      .data-row {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid #d9dedb;
      }

      .chart-track {
        height: 6px;
        margin-top: 12px;
        display: flex;
        overflow: hidden;
        background: #e8ece9;
      }

      .login-bar {
        width: 24px;
        background: #2455c3;
      }

      .login-bar:nth-child(2n) {
        background: #10115c;
      }
    </style>
  </head>
  <body>
    @php
      $statusRows = [
          ['label' => 'Activos', 'value' => (int) ($users['ativos'] ?? 0)],
          ['label' => 'Inactivos', 'value' => (int) ($users['inactivos'] ?? 0)],
          ['label' => 'Suspensos', 'value' => (int) ($users['suspensos'] ?? 0)],
      ];
      $typeRows = [
          ['label' => 'Directores', 'value' => (int) ($users['diretores'] ?? 0)],
          ['label' => 'Subdirectores', 'value' => (int) ($users['subdiretores'] ?? 0)],
          ['label' => 'Professores', 'value' => (int) ($users['professores'] ?? 0)],
          ['label' => 'Alunos', 'value' => (int) ($users['alunos'] ?? 0)],
      ];
      $statusTotal = array_sum(array_column($statusRows, 'value'));
      $typeTotal = array_sum(array_column($typeRows, 'value'));
      $loginTotal = array_sum(array_column($logins, 'logins'));
      $averageLogins = count($logins) > 0 ? round($loginTotal / count($logins), 1) : 0;
      $maxLogins = max(array_column($logins, 'logins') ?: [0]);
    @endphp

    <main class="page">
      <header style="display: flex; justify-content: space-between; align-items: center">
        <div style="display: flex; align-items: center; gap: 12px">
          <img src="{{ $logoDataUri }}" alt="TungaNetu" style="width: 170px; height: auto" />
          <div style="font-size: 12px; font-weight: 600">{{ $instituicao }}</div>
        </div>
        <div class="muted" style="font-size: 10px; text-align: right">
          Relatório de utilização<br />Emitido em {{ $dataEmissao }}
        </div>
      </header>

      <section style="margin-top: 56px">
        <h1 class="accent" style="margin: 0; font-family: Georgia, serif; font-size: 34px; font-weight: 400; line-height: 1.15">
          Relatório de Utilização
        </h1>
        <div class="accent" style="margin-top: 12px; font-size: 14px; font-weight: 600">
          {{ $instituicao }}
        </div>
      </section>

      <section style="margin-top: 28px; display: flex; gap: 36px; font-size: 11px">
        <div>
          <div class="accent" style="font-size: 10px; font-weight: 600">Responsável</div>
          <div style="font-weight: 500">{{ $responsavel }}</div>
        </div>
        <div>
          <div class="accent" style="font-size: 10px; font-weight: 600">Domínio</div>
          <div style="font-weight: 500">{{ $dominio }}</div>
        </div>
        <div>
          <div class="accent" style="font-size: 10px; font-weight: 600">Período</div>
          <div style="font-weight: 500">{{ $inicioPeriodo }} a {{ $fimPeriodo }}</div>
        </div>
      </section>

      <section class="metric-row" style="margin-top: 26px">
        <div class="metric">
          <div class="metric-value">{{ $users['ativos'] ?? 0 }}</div>
          <div style="font-size: 10px; line-height: 1.35; color: #4d5b54">
            Usuários activos<br />{{ $statusTotal > 0 ? number_format(($users['ativos'] / $statusTotal) * 100, 1, ',', '.') : '0,0' }}% do total
          </div>
        </div>
        <div class="metric">
          <div class="metric-value">{{ $typeTotal }}</div>
          <div style="font-size: 10px; line-height: 1.35; color: #4d5b54">
            Perfis registados<br />Na instituição
          </div>
        </div>
        <div class="metric">
          <div class="metric-value">{{ $loginTotal }}</div>
          <div style="font-size: 10px; line-height: 1.35; color: #4d5b54">
            Utilizadores com login<br />Média de {{ number_format($averageLogins, 1, ',', '.') }} por dia
          </div>
        </div>
      </section>

      <section style="margin-top: 34px; display: flex; gap: 44px">
        <div style="flex: 1">
          <h2 class="accent" style="margin: 0 0 12px; font-family: Georgia, serif; font-size: 16px">
            Estado dos usuários
          </h2>
          <div style="border-top: 1px solid #d9dedb">
            @foreach ($statusRows as $row)
              <div class="data-row">
                <span>{{ $row['label'] }}</span>
                <span>
                  <b>{{ $row['value'] }}</b>
                  <span class="muted">· {{ $statusTotal > 0 ? number_format(($row['value'] / $statusTotal) * 100, 1, ',', '.') : '0,0' }}%</span>
                </span>
              </div>
            @endforeach
          </div>
          <div class="chart-track">
            @foreach ($statusRows as $index => $row)
              <div style="width: {{ $statusTotal > 0 ? ($row['value'] / $statusTotal) * 100 : 0 }}%; background: {{ ['#10115c', '#f28c1b', '#2455c3'][$index] }}"></div>
            @endforeach
          </div>
        </div>

        <div style="flex: 1">
          <h2 class="accent" style="margin: 0 0 12px; font-family: Georgia, serif; font-size: 16px">
            Distribuição por perfil
          </h2>
          <div style="border-top: 1px solid #d9dedb">
            @foreach ($typeRows as $row)
              <div class="data-row">
                <span>{{ $row['label'] }}</span>
                <span>
                  <b>{{ $row['value'] }}</b>
                  <span class="muted">· {{ $typeTotal > 0 ? number_format(($row['value'] / $typeTotal) * 100, 1, ',', '.') : '0,0' }}%</span>
                </span>
              </div>
            @endforeach
          </div>
          <div class="chart-track">
            @foreach ($typeRows as $index => $row)
              <div style="width: {{ $typeTotal > 0 ? ($row['value'] / $typeTotal) * 100 : 0 }}%; background: {{ ['#10115c', '#2455c3', '#f28c1b', '#328b63'][$index] }}"></div>
            @endforeach
          </div>
        </div>
      </section>

      <section style="margin-top: 34px">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px">
          <h2 class="accent" style="margin: 0; font-family: Georgia, serif; font-size: 16px">
            Logins nos últimos 7 dias
          </h2>
          <span class="muted" style="font-size: 10px">{{ $loginTotal }} utilizadores</span>
        </div>
        <div style="height: 126px; padding: 12px 16px 0; display: flex; align-items: flex-end; justify-content: space-between; border-top: 1px solid #d9dedb; border-bottom: 1px solid #d9dedb">
          @foreach ($logins as $dia)
            <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 5px">
              <span style="font-size: 10px; font-weight: 600">{{ $dia['logins'] }}</span>
              <div class="login-bar" style="height: {{ $maxLogins > 0 ? max(4, ($dia['logins'] / $maxLogins) * 76) : 4 }}px"></div>
              <span class="muted" style="font-size: 10px">{{ $dia['dia'] }}</span>
            </div>
          @endforeach
        </div>
      </section>

      <aside style="margin-top: 28px; padding: 12px 16px; background: #fbf3e8; color: #8a7a64; font-size: 11px">
        Os dados apresentados correspondem aos registos disponíveis nesta instituição no momento da emissão.
      </aside>

      <footer class="muted" style="margin-top: auto; padding-top: 10px; display: flex; justify-content: space-between; font-size: 10px">
        <span>TungaNetu | {{ $instituicao }}</span>
        <span>Confidencial | {{ $dataEmissao }}</span>
      </footer>
    </main>
  </body>
</html>