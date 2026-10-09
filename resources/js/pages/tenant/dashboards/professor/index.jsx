import { usePage } from '@inertiajs/react';
import { GreetingHeader } from './components/greeting-header';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { DashboardSummary } from './components/dashboard-summary';
import { ProximasAulas } from './components/proximas-aulas';
import { AvisosEventos } from './components/avisos-eventos';
import { DashboardPanel } from '@/components/dashboard-panel';

export default function ProfessorDashboard({
  proximasAulas = [],
  avisos = [],
}) {
  const { auth } = usePage().props;
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();

  return (
    <div className="space-y-6 p-6">
      <GreetingHeader
        greeting={greeting}
        userName={auth?.user?.nome}
        todayFormatted={todayFormatted}
      />

      <DashboardSummary aulas={proximasAulas} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <DashboardPanel
          title="Próximas Aulas"
          description="Aulas programadas para os próximos dias"
          colSpan="lg:col-span-3"
        >
          <ProximasAulas data={proximasAulas} />
        </DashboardPanel>

        <DashboardPanel
          title="Avisos & Eventos"
          description="Avisos e eventos importantes"
          colSpan="lg:col-span-2"
        >
          <AvisosEventos data={avisos} />
        </DashboardPanel>
      </div>
    </div>
  );
}
