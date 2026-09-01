export const TUTELA_STATUS_CONFIG = {
  activo: {
    label: 'Activo',
    dot: 'bg-emerald-500',
    text: 'text-emerald-700 dark:text-emerald-400',
    bg: 'bg-emerald-50 dark:bg-emerald-950/40',
    pulse: true,
  },
  pendente: {
    label: 'Pendente de Aprovação',
    dot: 'bg-amber-500',
    text: 'text-amber-700 dark:text-amber-400',
    bg: 'bg-amber-50 dark:bg-amber-950/40',
    pulse: true,
  },
  rejeitado: {
    label: 'Rejeitado',
    dot: 'bg-orange-500',
    text: 'text-orange-700 dark:text-orange-400',
    bg: 'bg-orange-50 dark:bg-orange-950/40',
    pulse: false,
  },
  encerrado: {
    label: 'Encerrado',
    dot: 'bg-red-500',
    text: 'text-red-700 dark:text-red-400',
    bg: 'bg-red-50 dark:bg-red-950/40',
    pulse: false,
  },
};

export function getTutelaStatusConfig(status) {
  // Se não há shared local, a tutela é própria e deve aparecer como activa.
  const normalizedStatus = (status ?? 'activo').toString().toLowerCase();

  return TUTELA_STATUS_CONFIG[normalizedStatus] ?? TUTELA_STATUS_CONFIG.activo;
}
