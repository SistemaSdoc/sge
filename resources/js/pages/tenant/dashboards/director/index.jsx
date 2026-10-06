import { ActionFeed } from './components/feed-accoes';
import { DashboardSummary } from './components/dashboard-summary';
import { GreetingHeader } from './components/greeting-header';
import { MetricsBar } from './components/metricas';
import { ProximosEventos } from './components/proximos-eventos';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { usePage } from '@inertiajs/react';
import { DashboardPanel } from '@/components/dashboard-panel';

export default function DirectorDashboard({
  metricas = [],
  accoes = [],
  eventos = [],
  can = {},
}) {
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();
  const { auth } = usePage().props;
  const acoesComPendencias = accoes.filter(
    (acao) => Number(acao.count) > 0,
  );

  return (
    <div className="space-y-6 p-6">
      <GreetingHeader
        greeting={greeting}
        userName={auth.user?.nome}
        todayFormatted={todayFormatted}
      />

      <DashboardSummary items={acoesComPendencias} />

      <MetricsBar metrics={metricas} canViewProfessores={can.viewProfessores} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <DashboardPanel title="Ações Pendentes" colSpan="lg:col-span-2">
          <ActionFeed items={acoesComPendencias} />
        </DashboardPanel>

        <DashboardPanel title="Próximos Eventos" colSpan="lg:col-span-1">
          <ProximosEventos events={eventos} />
        </DashboardPanel>
      </div>
    </div>
  );
}
