/**
 * Utilitários para configurações de urgência e severidade
 */
import { AlertCircle, Zap } from 'lucide-react';

export const severityConfig = {
  critical: {
    color: 'bg-red-500',
    badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
  },
  warning: {
    color: 'bg-amber-500',
    badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',
  },
  attention: {
    color: 'bg-orange-500',
    badge:
      'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
  },
  success: {
    color: 'bg-emerald-500',
    badge:
      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',
  },
};

export const eventTypeConfig = {
  aviso: {
    label: 'Aviso',
    badge:
      'bg-yellow-50 text-yellow-500 dark:bg-yellow-900 dark:text-yellow-100',
  },
  urgente: {
    label: 'Urgente',
    badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
  },
  evento: {
    label: 'Evento',
    badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
  },
};

export function getUrgencyConfig(items) {
  const hasCritical = items?.some((i) => i.severity === 'critical');
  const hasWarning = items?.some((i) => i.severity === 'warning');

  if (hasCritical) {
    return {
      variant: 'outline',
      borderClass: 'border-red-200 dark:border-red-900/30',
      bgClass: 'bg-red-50 dark:bg-red-950/20',
      iconClass: 'text-red-600 dark:text-red-400',
      textClass: 'text-red-700 dark:text-red-300',
      label: 'Atenção:',
      Icon: AlertCircle,
    };
  }

  if (hasWarning) {
    return {
      variant: 'outline',
      borderClass: 'border-amber-200 dark:border-amber-900/30',
      bgClass: 'bg-amber-50 dark:bg-amber-950/20',
      iconClass: 'text-amber-600 dark:text-amber-400',
      textClass: 'text-amber-700 dark:text-amber-300',
      label: 'Importante:',
      Icon: AlertCircle,
    };
  }

  return {
    variant: 'outline',
    borderClass: 'border-blue-200 dark:border-blue-900/30',
    bgClass: 'bg-blue-50 dark:bg-blue-950/20',
    iconClass: 'text-blue-600 dark:text-blue-400',
    textClass: 'text-blue-700 dark:text-blue-300',
    label: 'Dica:',
    Icon: Zap,
  };
}

export function getEventType(type) {
  return (
    eventTypeConfig[type] || {
      label: type,
      badge: 'bg-primary/10 text-primary',
    }
  );
}

export function getSeverityConfig(severity) {
  return severityConfig[severity] || severityConfig.attention;
}
