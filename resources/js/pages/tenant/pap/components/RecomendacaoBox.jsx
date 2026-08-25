import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const ESTADO_CONFIG = {
  'melhoria-solicitada': {
    label: 'Melhoria solicitada',
    className: 'bg-yellow-100 text-yellow-800',
  },
  reprovado: { label: 'Reprovado', className: 'bg-red-100 text-red-800' },
  aprovado: { label: 'Aprovado', className: 'bg-green-100 text-green-800' },
  pendente: { label: 'Reenviado', className: 'bg-blue-100 text-blue-800' },
};

export default function RecomendacaoBox({ comentario, autor, data, estado }) {
  const config = ESTADO_CONFIG[estado] ?? {
    label: estado,
    className: 'bg-gray-100 text-gray-800',
  };

  return (
    <Card>
      <CardHeader className="pb-2">
        <div className="flex items-center justify-between gap-2">
          <CardTitle className="text-sm font-medium text-muted-foreground">
            {autor ?? 'Utilizador desconhecido'}
          </CardTitle>
          <div className="flex items-center gap-2">
            <Badge className={config.className}>{config.label}</Badge>
            {data && (
              <span className="text-xs text-muted-foreground">
                {new Date(data).toLocaleString('pt-PT', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </span>
            )}
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <div className="rounded-lg border bg-yellow-50 p-4">
          <p className="text-sm text-gray-700">
            {comentario || 'Nenhuma recomendação informada.'}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
