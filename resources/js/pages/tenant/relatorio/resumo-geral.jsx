import {
  ChartLegend,
  KpiChartCard,
  MiniBarChart,
  MiniDonutChart,
  MiniGroupedBarChart,
  MiniHorizontalBarChart,
  MiniLineChart,
  SeriesLegend,
} from '@/components/relatorio/KpiChartCard';

/**
 * ResumoGeral
 * Dashboard com os cards do relatório geral (tipo === 'geral').
 * Recebe exactamente os dados devolvidos por RelatorioService::resumoGeral():
 * stats, graficos, kpis_extra — mais `pode_ver_pagamentos`, enviado pelo
 * controller com base na ItemPagavelPolicy (só instituições do tipo
 * "colegio" veem o card de Pagamentos).
 *
 * Cada card tem o seu próprio `formatValor`, usado pelo tooltip do gráfico
 * ao passar o rato — o texto é diferente em cada um porque a unidade do dado
 * também é diferente (alunos, %, grupos, Kz, documentos...).
 */
export default function ResumoGeral({
  stats,
  graficos,
  kpis_extra: kpisExtra,
  pode_ver_pagamentos: podeVerPagamentos = true,
}) {
  const valor = (label) => stats.find((s) => s.label === label)?.valor ?? '—';

  return (
    <div className="flex flex-col gap-4">
      <div>
        <h2 className="font-heading text-lg font-semibold text-foreground">
          Métricas do relatório
        </h2>
        <p className="text-sm text-muted-foreground">
          Acompanhe os principais indicadores da instituição
        </p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <KpiChartCard
          titulo="Total de alunos da instituição"
          descricao="Matriculados por classe e turno"
          valor={valor('Alunos')}
          chart={
            <MiniGroupedBarChart
              data={graficos.alunos_por_classe.dados}
              series={graficos.alunos_por_classe.series}
              formatValor={(v) => `${v} alunos matriculados`}
            />
          }
          legend={<SeriesLegend series={graficos.alunos_por_classe.series} />}
        />

        <KpiChartCard
          titulo="Professores"
          descricao="Distribuição por especialidade"
          valor={valor('Professores')}
          chart={<MiniDonutChart data={graficos.professores_por_especialidade} formatValor={(v) => `${v} professor${v === 1 ? '' : 'es'}`} />}
          legend={<ChartLegend data={graficos.professores_por_especialidade} />}
        />

        <KpiChartCard
          titulo="Total de turmas"
          descricao="Ocupação de vagas por classe"
          valor={valor('Turmas')}
          chart={<MiniBarChart data={graficos.turmas_ocupacao} color="#10b981" formatValor={(v) => `${v}% de vaga ocupada`} />}
        />

        <KpiChartCard
          titulo="PAP - Total de finalistas"
          descricao="Estado de aprovação dos grupos"
          valor={kpisExtra.pap_finalistas}
          chart={<MiniDonutChart data={graficos.pap_por_estado} formatValor={(v) => `${v} grupo${v === 1 ? '' : 's'}`} />}
          legend={<ChartLegend data={graficos.pap_por_estado} />}
        />

        {podeVerPagamentos && (
          <KpiChartCard
            titulo="Pagamentos efectuados"
            descricao="Receita arrecadada nos últimos 6 meses"
            valor={kpisExtra.pagamentos_adimplencia !== null ? `${kpisExtra.pagamentos_adimplencia}% Taxa de cobrança` : 'Taxa de cobrança — (pendente)'}
            chart={<MiniLineChart data={graficos.pagamentos_mensal} color="#3b82f6" formatValor={(v) => `${v.toLocaleString()} Kz arrecadados`} />}
          />
        )}

        <KpiChartCard
          titulo="Total de documentos emitidos"
          descricao="Emitidos nos últimos 30 dias"
          valor={kpisExtra.documentos_emitidos ?? '—'}
          chart={<MiniHorizontalBarChart data={graficos.documentos_por_tipo} color="#8b5cf6" formatValor={(v) => `${v} emitido${v === 1 ? '' : 's'}`} />}
        />
      </div>
    </div>
  );
}