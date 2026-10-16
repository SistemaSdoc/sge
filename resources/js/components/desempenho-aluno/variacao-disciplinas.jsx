import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { fmt } from './helpers';

export function VariacaoDisciplinas({ disciplinas }) {
  const itens = (disciplinas ?? [])
    .map((d) => {
      const t = d.trimestres ?? [];
      const validos = t.filter((v) => v !== null && v !== undefined);
      if (validos.length < 2) return null;
      const primeiro = validos[0];
      const ultimo = validos[validos.length - 1];
      return {
        disciplina: d.disciplina,
        variacao: Number((ultimo - primeiro).toFixed(1)),
      };
    })
    .filter(Boolean);

  const temAlgum = itens.length > 0;
  const maxAbs = Math.max(1, ...itens.map((i) => Math.abs(i.variacao)));

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-sm">Variação por disciplina</CardTitle>
        <p className="text-xs text-muted-foreground">
          Diferença entre o 1.º e o último trimestre
        </p>
      </CardHeader>
      <CardContent>
        {!temAlgum ? (
          <div className="flex h-40 items-center justify-center bg-muted text-xs text-muted-foreground">
            Precisa de pelo menos 2 trimestres
          </div>
        ) : (
          <div className="flex flex-col gap-3">
            {itens.map((item, i) => (
              <Linha key={i} item={item} maxAbs={maxAbs} />
            ))}
            <div className="flex justify-between px-1 text-[10px] text-muted-foreground">
              <span>← desceu</span>
              <span>subiu →</span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function Linha({ item, maxAbs }) {
  const positivo = item.variacao > 0;
  const negativo = item.variacao < 0;
  const largura = Math.min(50, (Math.abs(item.variacao) / maxAbs) * 50);

  return (
    <div className="grid grid-cols-[7rem_1fr] items-center gap-3 text-xs">
      <span className="truncate text-right text-muted-foreground" title={item.disciplina}>
        {item.disciplina}
      </span>
      <div className="relative h-6">
        <div className="absolute inset-y-0 left-1/2 w-px bg-border" />
        <div
          className={cn(
            'absolute top-1/2 h-3 -translate-y-1/2',
            positivo && 'bg-green-600',
            negativo && 'bg-destructive',
            !positivo && !negativo && 'bg-muted-foreground'
          )}
          style={{
            left: positivo ? '50%' : `${50 - largura}%`,
            width: `${largura}%`,
          }}
        />
        <span
          className={cn(
            'absolute top-1/2 -translate-y-1/2 text-[10px] font-medium',
            positivo && 'text-green-600',
            negativo && 'text-destructive'
          )}
          style={{
            left: positivo ? `calc(50% + ${largura}% + 6px)` : undefined,
            right: negativo ? `calc(50% + ${largura}% + 6px)` : undefined,
          }}
        >
          {positivo ? '+' : ''}
          {fmt(item.variacao)}
        </span>
      </div>
    </div>
  );
}