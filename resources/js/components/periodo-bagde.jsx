import { Badge } from '@/components/ui/badge';

const CORES = {
  '1º Trimestre': 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800',
  '2º Trimestre': 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
  '3º Trimestre': 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800',
  '1º Semestre':  'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800',
  '2º Semestre':  'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800',
  'Exame Especial': 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800',
  'Recursos':     'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
};

export function PeriodoBadge({ periodo, className = '' }) {
  if (!periodo) return null;

  const cores = CORES[periodo] ?? 'bg-muted text-muted-foreground border-muted';

  return (
    <Badge variant="outline" className={`${cores} ${className}`}>
      {periodo}
    </Badge>
  );
}