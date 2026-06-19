import { DashboardSummary } from './components/dashboard-summary';
import { GreetingHeader } from './components/greeting-header';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { ProximasAulas } from './components/proximas-aulas';
import { AvisosEventos } from './components/avisos-eventos/index';
import { usePage } from '@inertiajs/react';
import { DashboardPanel } from '@/components/dashboard-panel';

export default function ProfessorDashboard({
  proximasAulas = [],
  avisos = [],
}) {
  const { user } = usePage().props;
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();

  return (
    <div className="space-y-6 p-6">
      <GreetingHeader
        greeting={greeting}
        userName={user?.nome}
        todayFormatted={todayFormatted}
      />

      <DashboardSummary aulas={proximasAulas} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <DashboardPanel title="Próximas Aulas" colSpan="lg:col-span-3">
          <ProximasAulas data={proximasAulas} />
        </DashboardPanel>

        <DashboardPanel title="Avisos & Eventos" colSpan="lg:col-span-2">
          <AvisosEventos data={avisos} />
        </DashboardPanel>
      </div>
    </div>
  );
}
