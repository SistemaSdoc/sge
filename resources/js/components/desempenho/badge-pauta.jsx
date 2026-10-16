import { Badge } from '@/components/ui/badge';

const ESTADOS = {
  por_iniciar: { label: 'Por iniciar', variant: 'outline' },
  em_curso: { label: 'Em curso', variant: 'secondary' },
  rascunho: { label: 'Rascunho', variant: 'secondary' },
  submetida: { label: 'Submetida', variant: 'default' },
  fechada: { label: 'Fechada', variant: 'default' },
};

export default function BadgePauta({ estado = 'por_iniciar' }) {
  const cfg = ESTADOS[estado] ?? { label: String(estado), variant: 'outline' };

  return <Badge variant={cfg.variant}>{cfg.label}</Badge>;
}
