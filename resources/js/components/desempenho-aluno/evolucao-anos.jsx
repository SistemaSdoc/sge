import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { fmt } from './helpers';

export function EvolucaoAnos({ anos }) {
  const dados = anos ?? [];
  const temAlgum = dados.some((a) => a.media !== null && a.media !== undefined);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-sm">Evolução por ano lectivo</CardTitle>
        <p className="text-xs text-muted-foreground">
          Média geral de cada ano · escala 0–20
        </p>
      </CardHeader>
      <CardContent>
        {!temAlgum ? (
          <div className="flex h-40 items-center justify-center bg-muted text-xs text-muted-foreground">
            Sem histórico
          </div>
        ) : (
          <div className="flex flex-col gap-2">
            <div className="relative flex h-40 items-end justify-around border-b px-2">
              <div
                className="absolute left-2 right-2 border-t border-dashed border-amber-500"
                style={{ bottom: '50%' }}
              >
                <span className="absolute right-0 -top-3.5 bg-card px-1 text-[9px] font-medium text-amber-600">
                  mínimo 10
                </span>
              </div>
              {dados.map((a, i) => {
                const vazio = a.media === null || a.media === undefined;
                const altura = vazio ? 6 : Math.min(100, (a.media / 20) * 100);
                return (
                  <div key={i} className="flex flex-1 flex-col items-center gap-2">
                    <span
                      className={cn(
                        'text-[10px] font-medium',
                        vazio && 'text-muted-foreground'
                      )}
                    >
                      {vazio ? '—' : fmt(a.media)}
                    </span>
                    <div
                      className={cn(
                        'w-12',
                        vazio ? 'bg-muted' : a.activo ? 'bg-blue-600' : 'bg-blue-400'
                      )}
                      style={{ height: `${altura}%` }}
                    />
                  </div>
                );
              })}
            </div>
            <div className="flex justify-around px-2 text-xs text-muted-foreground">
              {dados.map((a, i) => (
                <span key={i} className="w-12 truncate text-center">
                  {a.ano}
                </span>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}