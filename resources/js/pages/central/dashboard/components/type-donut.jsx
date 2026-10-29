import { Pie, PieChart, ResponsiveContainer } from 'recharts';

export function TypeDonut({ institutos = 0, colegios = 0 }) {
  const total = institutos + colegios;
  const data = [
    { name: 'Institutos', value: institutos, fill: 'var(--color-info)' },
    { name: 'Colégios', value: colegios, fill: 'var(--color-warning)' },
  ];

  return (
    <div className="flex h-full min-h-64 flex-col items-center justify-center gap-6">
      <div className="relative size-56">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              innerRadius={72}
              outerRadius={104}
              startAngle={90}
              endAngle={-270}
              strokeWidth={2}
            />
          </PieChart>
        </ResponsiveContainer>
        <span className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-3xl font-bold leading-none">{total}</span>
          <span className="mt-1 text-xs text-muted-foreground">Total</span>
        </span>
      </div>

      <ul className="flex flex-wrap items-center justify-center gap-x-8 gap-y-2 text-sm">
        {data.map((item) => (
          <li key={item.name} className="flex items-center gap-2">
            <span className="size-2.5" style={{ background: item.fill }} />
            <span className="text-muted-foreground">{item.name}</span>
            <b>{item.value}</b>
            <span className="text-xs text-muted-foreground">
              ({total ? Math.round((item.value / total) * 100) : 0}%)
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}