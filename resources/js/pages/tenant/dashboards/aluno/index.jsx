import { DashboardSummary } from './components/dashboard-summary';
import { GreetingHeader } from './components/greeting-header';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { ProximasAulas } from './components/proximas-aulas';
import { AvisosEventos } from './components/avisos-eventos/index';
import { usePage } from '@inertiajs/react';
import { DashboardPanel } from '@/components/dashboard-panel';

export default function AlunoDashboard({ proximasAulas = [], avisos = [] }) {
  const { auth } = usePage().props;
  const greeting = getGreeting();
  const todayFormatted = getTodayFormatted();

  return (
    <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
      <GreetingHeader
        greeting={greeting}
        userName={auth?.user?.nome}
        todayFormatted={todayFormatted}
      />

      <DashboardSummary aulas={proximasAulas} />

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-5 lg:gap-6">
        <DashboardPanel
          title="Próximas Aulas"
          description="Aulas programadas para os próximos dias"
          colSpan="lg:col-span-3"
        >
          <ProximasAulas data={proximasAulas} />
        </DashboardPanel>

        <DashboardPanel
          title="Avisos & Eventos"
          description="Veja os avisos e eventos importantes"
          colSpan="lg:col-span-2"
        >
          <AvisosEventos data={avisos} />
        </DashboardPanel>
      </div>
    </div>
  );
}
