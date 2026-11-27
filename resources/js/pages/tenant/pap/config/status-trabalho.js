import { AlertCircle, CheckCircle, Clock, XCircle } from 'lucide-react';

const STATUS_TRABALHO = {
  pendente_entrega: {
    label: 'Aguarda entrega',
    icon: Clock,
    badge: 'bg-muted text-muted-foreground border-transparent',
  },
  em_analise_tutor: {
    label: 'Em análise — Tutor',
    icon: Clock,
    badge: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  correcao_tutor: {
    label: 'Correção solicitada — Tutor',
    icon: AlertCircle,
    badge: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  em_analise_coordenacao: {
    label: 'Em análise — Coordenação',
    icon: Clock,
    badge: 'bg-purple-50 text-purple-700 border-purple-200',
  },
  correcao_coordenacao: {
    label: 'Correção solicitada — Coordenação',
    icon: AlertCircle,
    badge: 'bg-orange-50 text-orange-700 border-orange-200',
  },
  aprovado: {
    label: 'Aprovado',
    icon: CheckCircle,
    badge: 'bg-green-50 text-green-700 border-green-200',
  },
  reprovado: {
    label: 'Reprovado',
    icon: XCircle,
    badge: 'bg-red-50 text-red-700 border-red-200',
  },
};

export const getStatusTrabalho = (status) =>
  STATUS_TRABALHO[status] ?? STATUS_TRABALHO.pendente_entrega;
