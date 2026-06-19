import { useAulaStatus } from './use-aula-status';
import { StateEmpty } from './states/state-empty';
import { StateLoading } from './states/state-loading';
import { StateHappening } from './states/state-happening';
import { StateToday } from './states/state-today';

export function DashboardSummary({ aulas = [], isLoading = false }) {
  const { status, proximaAula, timeLeft } = useAulaStatus(aulas);

  if (isLoading) return <StateLoading />;
  if (status === 'empty') return <StateEmpty />;
  if (status === 'happening') return <StateHappening aula={proximaAula} />;
  if (status === 'today')
    return <StateToday aula={proximaAula} timeLeft={timeLeft} />;

  return null;
}
