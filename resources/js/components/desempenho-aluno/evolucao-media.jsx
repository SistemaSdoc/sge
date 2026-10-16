import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { fmt } from './helpers';

export function EvolucaoMedia({ trimestres }) {
  const dados = trimestres ?? [];
  const temAlgum = dados.some((t) => t.media !== null && t.media !== undefined);

  const W = 420;
  const H = 160;
  const PAD_X = 24;
  const PAD_Y = 24;

  const x = (i) => PAD_X + (i * (W - PAD_X * 2)) / Math.max(1, dados.length - 1);
  const y = (v) => H - PAD_Y - ((v ?? 0) / 20) * (H - PAD_Y * 2);
  const yMin = y(10);

  const pontosValidos = dados
    .map((t, i) => ({ i, v: t.media }))
    .filter((p) => p.v !== null && p.v !== undefined);

  const linhaPath = pontosValidos
    .map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${x(p.i)} ${y(p.v)}`)
    .join(' ');

  const areaPath =
    pontosValidos.length > 1
      ? `${linhaPath} L ${x(pontosValidos[pontosValidos.length - 1].i)} ${H - PAD_Y} L ${x(pontosValidos[0].i)} ${H - PAD_Y} Z`
      : '';

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-sm">Evolução da média</CardTitle>
        <p className="text-xs text-muted-foreground">
          Média geral em cada trimestre · escala 0–20
        </p>
      </CardHeader>
      <CardContent>
        {!temAlgum ? (
          <Vazio texto="Sem notas finalizadas" />
        ) : (
          <div className="flex flex-col gap-2">
            <svg viewBox={`0 0 ${W} ${H}`} className="w-full">
              <line
                x1={PAD_X}
                x2={W - PAD_X}
                y1={yMin}
                y2={yMin}
                stroke="currentColor"
                strokeWidth="1"
                strokeDasharray="4 4"
                className="text-amber-500"
              />
              {areaPath && <path d={areaPath} className="fill-blue-500/10" />}
              {linhaPath && (
                <path
                  d={linhaPath}
                  fill="none"
                  className="stroke-blue-600"
                  strokeWidth="2"
                />
              )}
              {pontosValidos.map((p) => (
                <g key={p.i}>
                  <circle cx={x(p.i)} cy={y(p.v)} r="4" className="fill-blue-600" />
                  <text
                    x={x(p.i)}
                    y={y(p.v) - 10}
                    textAnchor="middle"
                    className="fill-foreground text-[10px] font-medium"
                  >
                    {fmt(p.v)}
                  </text>
                </g>
              ))}
            </svg>
            <div className="flex justify-between px-6 text-xs text-muted-foreground">
              {dados.map((t, i) => (
                <span key={i}>{t.periodo}.º Trimestre</span>
              ))}
            </div>
            <div className="text-right text-[10px] text-amber-600">Mínimo 10</div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function Vazio({ texto }) {
  return (
    <div className="flex h-40 items-center justify-center bg-muted text-xs text-muted-foreground">
      {texto}
    </div>
  );
}