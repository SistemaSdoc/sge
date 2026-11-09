import {
  AlertCircle,
  CheckCircle,
  Clock,
  RefreshCw,
  XCircle,
} from 'lucide-react';

const STATUS_HISTORICO = {
  pendente: {
    label: 'Em análise — Coordenação',
    icon: Clock,
    badgeClass: 'bg-purple-50 text-purple-700 border-purple-200',
  },
  aprovado: {
    label: 'Aprovado',
    icon: CheckCircle,
    badgeClass: 'bg-green-50 text-green-700 border-green-200',
  },
  reprovado: {
    label: 'Reprovado',
    icon: XCircle,
    badgeClass: 'bg-red-50 text-red-700 border-red-200',
  },
  'melhoria-solicitada-tutor': {
    label: 'Correção solicitada — Tutor',
    icon: AlertCircle,
    badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  'melhoria-solicitada-coordenacao': {
    label: 'Correção solicitada — Coordenação',
    icon: AlertCircle,
    badgeClass: 'bg-orange-50 text-orange-700 border-orange-200',
  },
  submetido: {
    label: 'Reenviado',
    icon: RefreshCw,
    badgeClass: 'bg-blue-50 text-blue-700 border-blue-200',
  },
};

export const getStatusHistorico = (status) =>
  STATUS_HISTORICO[status] ?? STATUS_HISTORICO.pendente;
