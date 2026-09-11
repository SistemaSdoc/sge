import { usePage } from '@inertiajs/react';
import { GreetingHeader } from './components/greeting-header';
import { getGreeting, getTodayFormatted } from '@/utils/greeting';

export default function ProfessorDashboard({ proximasAulas = [], avisos = [] }) {
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
    </div>
  );
}