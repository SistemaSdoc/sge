import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from '@/components/ui/chart';
import { Bar, BarChart, XAxis, YAxis } from 'recharts';

const chartConfig = {
  logins: { label: 'Logins', color: 'var(--color-warning)' },
};

const EMPTY = [
  { dia: 'Seg', logins: 0 },
  { dia: 'Ter', logins: 0 },
  { dia: 'Qua', logins: 0 },
  { dia: 'Qui', logins: 0 },
  { dia: 'Sex', logins: 0 },
  { dia: 'Sáb', logins: 0 },
  { dia: 'Dom', logins: 0 },
];

export function UsersLoginsChart({ logins_semana }) {
  const data = logins_semana ?? EMPTY;
  const total = data.reduce((s, d) => s + d.logins, 0);
  const media = data.length > 0 ? Math.round(total / data.length) : 0;

  return (
    <div className="flex flex-col">
      <ChartContainer config={chartConfig} className="h-40 w-full">
        <BarChart data={data} barSize={20}>
          <XAxis
            dataKey="dia"
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 12 }}
            tickMargin={8}
          />
          <YAxis hide />
          <ChartTooltip
            cursor={false}
            content={<ChartTooltipContent hideLabel />}
          />
          <Bar
            dataKey="logins"
            fill="var(--color-warning)"
            isAnimationActive={true}
            animationDuration={800}
            animationEasing="ease-in-out"
          />
        </BarChart>
      </ChartContainer>

      <div className="mt-4 space-y-2">
        <div className="flex items-center justify-between text-sm">
          <div className="flex items-center gap-2">
            <span className="relative flex size-2">
              <span className="absolute inline-flex h-full w-full animate-pulse bg-(--color-warning) opacity-75" />
              <span className="relative inline-flex size-2 bg-(--color-warning)" />
            </span>
            <span className="text-muted-foreground">Total esta semana</span>
          </div>
          <span className="font-medium">{total}</span>
        </div>
        <div className="flex items-center justify-between text-sm">
          <div className="flex items-center gap-2">
            <span className="relative flex size-2">
              <span className="absolute inline-flex h-full w-full animate-pulse bg-(--color-info) opacity-75" />
              <span className="relative inline-flex size-2 bg-(--color-info)" />
            </span>
            <span className="text-muted-foreground">Média diária</span>
          </div>
          <span className="font-medium">{media}</span>
        </div>
      </div>
    </div>
  );
}
