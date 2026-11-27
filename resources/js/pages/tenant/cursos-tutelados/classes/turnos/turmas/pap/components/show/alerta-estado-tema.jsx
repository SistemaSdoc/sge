import { router } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import { editar as editarTema } from '@/actions/App/Http/Controllers/Tenant/GrupoPapAprovacaoController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

export function AlertaEstadoTema({ grupoPap, can }) {
  const estado = (grupoPap.status_aprovacao || '').toLowerCase();
  const isReprovado = estado === 'reprovado';
  const estadosCorrigiveis = [
    'reprovado',
    'melhoria-solicitada',
    'melhoria-solicitada-tutor',
    'melhoria-solicitada-coordenacao',
  ];

  if (!can?.corrigirTema || !estadosCorrigiveis.includes(estado)) {
    return null;
  }

  return (
    <Alert variant={isReprovado ? 'destructive' : 'default'}>
      <AlertCircle className="h-4 w-4" />
      <AlertTitle>
        {isReprovado ? 'Tema reprovado' : 'Melhoria solicitada'}
      </AlertTitle>
      <AlertDescription className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span className="text-sm">
          {grupoPap.comentario_aprovacao ?? 'Sem comentário adicional.'}
        </span>
        <Button
          size="sm"
          variant={isReprovado ? 'destructive' : 'default'}
          onClick={() =>
            router.visit(editarTema.url({ grupoPap: grupoPap.id }))
          }
        >
          {isReprovado ? 'Enviar Novo Tema' : 'Corrigir Tema'}
        </Button>
      </AlertDescription>
    </Alert>
  );
}
