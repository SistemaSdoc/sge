import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function FaltasTrimestre({ assiduidade }) {
  const porTri = assiduidade?.por_trimestre ?? [];
  const temAlgum = porTri.some((v) => v !== null && v !== undefined && v > 0);
  const max = Math.max(1, ...porTri.filter((v) => v !== null && v !== undefined));

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-sm">Faltas por trimestre</CardTitle>
        <p className="text-xs text-muted-foreground">
          Total de faltas em cada trimestre
        </p>
      </CardHeader>
      <CardContent>
        {!temAlgum ? (
          <div className="flex h-40 items-center justify-center bg-muted text-xs text-muted-foreground">
            Sem faltas registadas
          </div>
        ) : (
          <div className="flex flex-col gap-2">
            <div className="flex h-40 items-end justify-around border-b px-2">
              {porTri.map((faltas, i) => {
                const vazio = faltas === null || faltas === undefined;
                const altura = vazio ? 6 : Math.max(6, (faltas / max) * 100);
                return (
                  <div key={i} className="flex flex-1 flex-col items-center gap-2">
                    <span
                      className={cn(
                        'text-[10px] font-medium',
                        vazio && 'text-muted-foreground'
                      )}
                    >
                      {vazio ? '—' : faltas}
                    </span>
                    <div
                      className={cn('w-12', vazio ? 'bg-muted' : 'bg-amber-500')}
                      style={{ height: `${altura}%` }}
                    />
                  </div>
                );
              })}
            </div>
            <div className="flex justify-around px-2 text-xs text-muted-foreground">
              {porTri.map((_, i) => (
                <span key={i} className="w-12 text-center">
                  {i + 1}.º Trimestre
                </span>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}