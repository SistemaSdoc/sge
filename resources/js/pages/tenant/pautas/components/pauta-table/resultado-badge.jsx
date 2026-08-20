import { Badge } from '@/components/ui/badge';

const RESULTADO_CONFIG = {
  // Trimestral
  APTO: {
    label: 'Apto',
    className: 'bg-blue-50 text-blue-600 border-blue-200',
  },
  'N/APTO': {
    label: 'N/Apto',
    className: 'bg-red-50 text-destructive border-red-200',
  },

  // Final
  transita: {
    label: 'Transita',
    className: 'bg-blue-50 text-blue-600 border-blue-200',
  },
  transita_com_deficiencia: {
    label: 'c/ Def.',
    className: 'bg-amber-50 text-amber-600 border-amber-200',
  },
  recurso: {
    label: 'Recurso',
    className: 'bg-green-50 text-green-600 border-green-200',
  },
  EEF: {
    label: 'EEF',
    className: 'bg-orange-50 text-orange-600 border-orange-200',
  },

  // Recurso
  aprovado_recurso: {
    label: 'Transita',
    className: 'bg-green-50 text-green-600 border-green-200',
  },
  reprovado_recurso: {
    label: 'N/Transita',
    className: 'bg-red-50 text-destructive border-red-200',
  },
  pendente: {
    label: 'Incompleto',
    className: 'bg-muted text-muted-foreground',
  },

  // Sem dados
  incompleto: {
    label: 'Incompleto',
    className: 'bg-muted text-muted-foreground',
  },
};

export function ResultadoBadge({ resultado }) {
  if (resultado === null || resultado === undefined) {
    return <span className="text-sm text-muted-foreground">—</span>;
  }

  const config = RESULTADO_CONFIG[resultado] ?? {
    label: resultado,
    className: 'bg-muted text-muted-foreground',
  };

  return (
    <Badge
      variant="outline"
      className={`text-xs font-medium ${config.className}`}
    >
      {config.label}
    </Badge>
  );
}
