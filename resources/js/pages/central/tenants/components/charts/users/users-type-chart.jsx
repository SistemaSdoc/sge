import { ChartContainer } from '@/components/ui/chart';
import { Pie, PieChart } from 'recharts';

const chartConfig = {
  diretores: { label: 'Directores', color: 'var(--color-info)' },
  subdiretores: { label: 'Subdirectores', color: 'var(--chart-1)' },
  professores: { label: 'Professores', color: 'var(--color-warning)' },
  alunos: { label: 'Alunos', color: 'var(--color-success)' },
};

export function UsersTypeChart({ users = {} }) {
  const data = [
    {
      key: 'diretores',
      value: users.diretores ?? 0,
      fill: chartConfig.diretores.color,
    },
    {
      key: 'subdiretores',
      value: users.subdiretores ?? 0,
      fill: chartConfig.subdiretores.color,
    },
    {
      key: 'professores',
      value: users.professores ?? 0,
      fill: chartConfig.professores.color,
    },
    { key: 'alunos', value: users.alunos ?? 0, fill: chartConfig.alunos.color },
  ];

  const total = data.reduce((s, d) => s + d.value, 0);

  return (
    <div className="flex flex-col">
      <div className="relative flex items-center justify-center">
        <ChartContainer config={chartConfig} className="h-40 w-full">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="key"
              innerRadius={52}
              outerRadius={72}
              strokeWidth={2}
            />
          </PieChart>
        </ChartContainer>

        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-bold">{total}</span>
          <span className="text-xs text-muted-foreground">Total</span>
        </div>
      </div>

      <div className="mt-4 space-y-2">
        {data.map((item) => (
          <div
            key={item.key}
            className="flex items-center justify-between text-sm"
          >
            <div className="flex items-center gap-2">
              <span className="relative flex size-2">
                <span
                  className="absolute inline-flex h-full w-full animate-pulse opacity-75"
                  style={{ background: item.fill }}
                />
                <span
                  className="relative inline-flex size-2"
                  style={{ background: item.fill }}
                />
              </span>
              <span className="text-muted-foreground">
                {chartConfig[item.key]?.label}
              </span>
            </div>
            <div className="flex items-center gap-3">
              <span className="font-medium">{item.value}</span>
              <span className="w-10 text-right text-muted-foreground">
                {total > 0 ? ((item.value / total) * 100).toFixed(1) : 0}%
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
