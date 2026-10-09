import { DashboardSummary } from './components/dashboard-summary';
import { GreetingHeader } from './components/greeting-header';
import { ActionFeed } from './components/feed-accoes';
import { ProximosEventos } from './components/proximos-eventos';
import { MetricsBar } from './components/metricas';
import { TenantTable } from './components/tenant-table';
import { DashboardPanel } from '@/components/dashboard-panel';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { usePage } from '@inertiajs/react';
import { ACCOES } from '@/utils/central-static';

export default function CentralDashboard({ metricas = {}, tenants = [] }) {
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();
  const { auth } = usePage().props;
  const acoesComPendencias = ACCOES.filter(
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

      <MetricsBar metrics={metricas} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <DashboardPanel title="Ações Pendentes" colSpan="lg:col-span-2">
          <ActionFeed items={acoesComPendencias} />
        </DashboardPanel>

        <DashboardPanel title="Próximos Eventos" colSpan="lg:col-span-1">
          <ProximosEventos />
        </DashboardPanel>
      </div>

      <DashboardPanel title="Instituições Recentes">
        <TenantTable tenants={tenants} />
      </DashboardPanel>
    </div>
  );
}
