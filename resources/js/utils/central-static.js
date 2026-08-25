export const METRICAS_CONFIG = [
  { id: 'totalInstituicoes',   label: 'Total de Instituições', href: '/dashboard/tenants' },
  { id: 'instituicoesActivas', label: 'Instituições Activas',  href: '/dashboard/tenants' },
  { id: 'pendentes',           label: 'Pendentes',             href: '/dashboard/tenants' },
  { id: 'totalUtilizadores',   label: 'Total de Utilizadores', href: '/dashboard/users'   },
];

export const ACCOES = [
  { id: 1, title: 'Instituições Pendentes', description: 'Aguardando activação', count: 0, severity: 'critical', href: '/dashboard/tenants' },
  { id: 2, title: 'Utilizadores Pendentes', description: 'Aguardando aprovação', count: 0, severity: 'warning',  href: '/dashboard/users'   },
];

export const estadoBadge = {
  active:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  trial:     'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  pending:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  archived:  'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
};