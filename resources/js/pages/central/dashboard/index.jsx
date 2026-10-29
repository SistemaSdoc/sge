import { usePage, usePoll } from '@inertiajs/react';
import { DashboardPanel } from '@/components/dashboard-panel';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';
import { GreetingHeader } from './components/greeting-header';
import { GrowthChart } from './components/growth-chart';
import { KpiStrip } from './components/kpi-strip';
import { LoginsChart } from './components/logins-chart';
import { TypeDonut } from './components/type-donut';
import { UsersTable } from './components/users-table';

const COR_TIPO = { instituto: 'var(--color-warning)' };

const soma = (lista, campo) => lista.reduce((total, item) => total + (item[campo] ?? 0), 0);

export default function CentralDashboard({ dashboard = {} }) {
  const { auth } = usePage().props;
  const { kpis = {}, crescimento = [], utilizadores = {}, logins = null, online = null } = dashboard;

  // Actualiza os números sozinho a cada 30 segundos
  usePoll(30000, { only: ['dashboard'] });

  const lista = utilizadores.por_instituicao ?? [];
  const utilizadoresPorInstituicao = lista.slice(0, 5).map((item) => ({
    label: item.nome,
    total: item.total,
    professores: item.professores,
    alunos: item.alunos,
    color: COR_TIPO[item.tipo],
  }));
  const resto = lista.slice(5);
  if (resto.length) {
    utilizadoresPorInstituicao.push({
      label: `Outras (${resto.length})`,
      total: soma(resto, 'total'),
      professores: soma(resto, 'professores'),
      alunos: soma(resto, 'alunos'),
    });
  }

  return (
    <div className="space-y-6 p-4 md:p-6">
      <GreetingHeader greeting={getGreeting()} userName={auth.user?.nome} todayFormatted={getTodayFormatted()} />

      <KpiStrip kpis={kpis} utilizadores={utilizadores} logins={logins} online={online} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 [&>*]:h-full">
        <DashboardPanel title="Crescimento da plataforma" description="Instituições registadas nos últimos 7 dias, por tipo.">
          <GrowthChart data={crescimento} />
        </DashboardPanel>

        <DashboardPanel title="Logins por dia" description="Utilizadores que entraram em cada um dos últimos 7 dias.">
          <LoginsChart data={logins?.por_dia} />
        </DashboardPanel>

        <DashboardPanel title="Utilizadores por instituição" description="Número de utilizadores em cada instituição.">
          <UsersTable items={utilizadoresPorInstituicao} />
        </DashboardPanel>

        <DashboardPanel title="Tipo de instituição" description="Distribuição das instituições por tipo.">
          <TypeDonut institutos={kpis.institutos ?? 0} colegios={kpis.colegios ?? 0} />
        </DashboardPanel>
      </div>
    </div>
  );
}