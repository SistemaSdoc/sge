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
  avisos = [],
}) {
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();
  const { user } = usePage().props;

  return (
    <div className="space-y-6 p-6">
      <GreetingHeader
        greeting={greeting}
        userName={user?.nome}
        todayFormatted={todayFormatted}
      />

      <DashboardSummary items={accoes} isLoading={false} />

      <MetricsBar metrics={metricas} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <DashboardPanel title="Ações Pendentes" colSpan="lg:col-span-2">
          <ActionFeed items={accoes} isLoading={false} />
        </DashboardPanel>

        <DashboardPanel title="Próximos Eventos" colSpan="lg:col-span-1">
          <ProximosEventos events={avisos} />
        </DashboardPanel>
      </div>
    </div>
  );
}
