import { SummaryItem } from './summary-item';

export function StateLoading() {
  return (
    <SummaryItem className="border-slate-200 bg-slate-50 dark:border-slate-700/30 dark:bg-slate-900/20">
      <span className="text-slate-600 dark:text-slate-400">
        A carregar aulas...
      </span>
    </SummaryItem>
  );
}
