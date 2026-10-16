import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { formatarEstado } from './utils';

export default function PainelPap({ pap }) {
  const estados = Object.entries(pap?.por_estado ?? {});

  return (
    <Card>
      <CardHeader>
        <CardTitle>Grupos PAP orientados</CardTitle>
        <CardAction>
          <Badge variant="secondary">Total: {pap?.total ?? 0}</Badge>
        </CardAction>
      </CardHeader>
      <CardContent>
        {estados.length === 0 ? (
          <p className="text-muted-foreground">
            Sem grupos PAP para os filtros seleccionados.
          </p>
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {estados.map(([estado, total]) => (
              <div key={estado} className="bg-muted p-4 text-center">
                <p className="text-2xl font-bold">{total}</p>
                <p className="text-muted-foreground">
                  {formatarEstado(estado)}
                </p>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
