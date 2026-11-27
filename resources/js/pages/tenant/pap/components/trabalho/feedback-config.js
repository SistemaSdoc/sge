import { AlertCircle, CheckCircle, XCircle } from 'lucide-react';

export const FEEDBACK_CONFIG = {
  correcao_tutor: {
    label: 'Correção solicitada pelo tutor',
    color: 'text-amber-700',
    bg: 'bg-amber-50 border-amber-200',
    icon: AlertCircle,
  },
  aprovacao_tutor: {
    label: 'Aprovado pelo tutor — enviado para coordenação',
    color: 'text-blue-700',
    bg: 'bg-blue-50 border-blue-200',
    icon: CheckCircle,
  },
  correcao_coordenacao: {
    label: 'Correção solicitada pela coordenação',
    color: 'text-orange-700',
    bg: 'bg-orange-50 border-orange-200',
    icon: AlertCircle,
  },
  aprovacao_coordenacao: {
    label: 'Aprovado pela coordenação',
    color: 'text-green-700',
    bg: 'bg-green-50 border-green-200',
    icon: CheckCircle,
  },
  reprovacao_coordenacao: {
    label: 'Reprovado pela coordenação',
    color: 'text-red-700',
    bg: 'bg-red-50 border-red-200',
    icon: XCircle,
  },
};
