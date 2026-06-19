import { SummaryItem } from './summary-item';

export function StateEmpty() {
  return (
    <SummaryItem className="border-emerald-200 bg-emerald-50 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <span className="font-semibold text-emerald-700 dark:text-emerald-300">
        Sem aulas para hoje.
      </span>
    </SummaryItem>
  );
}
