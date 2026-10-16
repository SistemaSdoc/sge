import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { fmt } from './helpers';

export function PontosFortes({ disciplinas }) {
  const itens = [...(disciplinas ?? [])]
    .filter((d) => d.media !== null && d.media !== undefined)
    .sort((a, b) => b.media - a.media)
    .slice(0, 3);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-sm">
          <span className="inline-block size-2.5 bg-green-600" />
          Pontos fortes
        </CardTitle>
        <p className="text-xs text-muted-foreground">
          Disciplinas com melhor média no ano lectivo
        </p>
      </CardHeader>
      <CardContent>
        {itens.length === 0 ? (
          <div className="flex h-24 items-center justify-center bg-muted text-xs text-muted-foreground">
            Sem dados
          </div>
        ) : (
          <div className="flex flex-col gap-3">
            {itens.map((d) => {
              const largura = Math.min(100, (d.media / 20) * 100);
              return (
                <div key={d.disciplina} className="flex flex-col gap-1">
                  <div className="flex items-center justify-between text-xs">
                    <span className="truncate">{d.disciplina}</span>
                    <span className="font-medium">{fmt(d.media)}</span>
                  </div>
                  <div className="h-2 bg-muted">
                    <div
                      className="bg-green-600"
                      style={{ width: `${largura}%`, height: '100%' }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
