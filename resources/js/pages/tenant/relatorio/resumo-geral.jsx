import {
  KpiChartCard,
  MiniBarChart,
  MiniDonutChart,
  MiniHorizontalBarChart,
  MiniLineChart,
} from '@/components/relatorio/KpiChartCard';

/**
 * ResumoGeral
 * Dashboard com os 6 cards do relatório geral (tipo === 'geral').
 * Recebe exactamente os dados devolvidos por RelatorioService::resumoGeral():
 * stats, graficos, kpis_extra.
 */
export default function ResumoGeral({ stats, graficos, kpis_extra: kpisExtra }) {
  const valor = (label) => stats.find((s) => s.label === label)?.valor ?? '—';

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <KpiChartCard
        titulo="Alunos"
        valor={valor('Alunos')}
        chart={<MiniBarChart data={graficos.alunos_por_classe} color="#3b82f6" />}
      />

      <KpiChartCard
        titulo="Professores"
        valor={valor('Professores')}
        chart={<MiniDonutChart data={graficos.professores_por_especialidade} />}
      />

      <KpiChartCard
        titulo="Turmas"
        valor={valor('Turmas')}
        chart={<MiniBarChart data={graficos.turmas_ocupacao} color="#10b981" />}
      />

      <KpiChartCard
        titulo="PAP finalistas"
        valor={kpisExtra.pap_finalistas}
        chart={<MiniDonutChart data={graficos.pap_por_estado} />}
      />

      <KpiChartCard
        titulo="Pagamentos"
        valor={
          kpisExtra.pagamentos_adimplencia !== null
            ? `${kpisExtra.pagamentos_adimplencia}% adimplência`
            : 'Adimplência — (pendente)'
        }
        chart={<MiniLineChart data={graficos.pagamentos_mensal} color="#3b82f6" />}
      />

      <KpiChartCard
        titulo="Documentos"
        valor={kpisExtra.documentos_pendentes ?? '—'}
        chart={<MiniHorizontalBarChart data={graficos.documentos_por_tipo} color="#8b5cf6" />}
      />
    </div>
  );
}