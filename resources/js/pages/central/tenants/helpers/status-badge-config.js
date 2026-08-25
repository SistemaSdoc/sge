export const STATUS_CONFIG = {
  // Tenant Statuses
  active: {
    label: 'Activo',
    dot: 'bg-emerald-500',
    text: 'text-emerald-700 dark:text-emerald-400',
    bg: 'bg-emerald-50 dark:bg-emerald-950/40',
    pulse: true,
  },
  trial: {
    label: 'Período de Teste',
    dot: 'bg-blue-500',
    text: 'text-blue-700 dark:text-blue-400',
    bg: 'bg-blue-50 dark:bg-blue-950/40',
    pulse: true,
  },
  pending: {
    label: 'Pendente de Verificação',
    dot: 'bg-amber-500',
    text: 'text-amber-700 dark:text-amber-400',
    bg: 'bg-amber-50 dark:bg-amber-950/40',
    pulse: true,
  },
  provisioning: {
    label: 'A configurar',
    dot: 'bg-orange-500',
    text: 'text-orange-700 dark:text-orange-400',
    bg: 'bg-orange-50 dark:bg-orange-950/40',
    pulse: true,
  },
  failed: {
    label: 'Falha na configuração',
    dot: 'bg-red-500',
    text: 'text-red-700 dark:text-red-400',
    bg: 'bg-red-50 dark:bg-red-950/40',
    pulse: false,
  },
  suspended: {
    label: 'Suspenso',
    dot: 'bg-red-500',
    text: 'text-red-700 dark:text-red-400',
    bg: 'bg-red-50 dark:bg-red-950/40',
    pulse: false,
  },
  inactive: {
    label: 'Inactivo',
    dot: 'bg-muted-foreground/50',
    text: 'text-muted-foreground',
    bg: 'bg-muted',
    pulse: false,
  },
  archived: {
    label: 'Arquivado',
    dot: 'bg-slate-400',
    text: 'text-slate-600 dark:text-slate-400',
    bg: 'bg-slate-50 dark:bg-slate-950/40',
    pulse: false,
  },
};

export function getStatusConfig(status) {
  return STATUS_CONFIG[status] ?? STATUS_CONFIG.inactive;
}
