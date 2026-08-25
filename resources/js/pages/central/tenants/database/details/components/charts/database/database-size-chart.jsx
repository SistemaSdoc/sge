import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  LabelList,
} from 'recharts';
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from '@/components/ui/chart';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

const chartConfig = {
  mb: {
    label: 'MB',
    color: 'var(--color-warning)',
  },
  label: {
    color: 'var(--background)',
  },
};

export function DatabaseSizeChart({ tenant, tabelas = [] }) {
  const data = tabelas.map((t) => ({
    nome: t.nome,
    mb: parseFloat(t.mb),
  }));

  // Altura dinâmica: ~18px por barra (espaçamento do barCategoryGap: 90%)
  const chartHeight = Math.max(300, data.length * 30);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Tamanho das tabelas</CardTitle>
        <CardDescription>Top 5 tabelas mais pesadas</CardDescription>
      </CardHeader>
      <CardContent>
        <ChartContainer
          config={chartConfig}
          style={{ height: `${chartHeight}px` }}
          className="w-full"
        >
          <BarChart
            accessibilityLayer
            data={data}
            layout="vertical"
            margin={{
              right: 16,
              left: 0,
              top: 0,
              bottom: 0,
            }}
            maxBarSize={100}
            barCategoryGap="90%"
          >
            <CartesianGrid horizontal={false} />
            <YAxis
              dataKey="nome"
              type="category"
              tickLine={false}
              tickMargin={10}
              axisLine={false}
              hide
            />
            <XAxis type="number" hide />
            <ChartTooltip
              cursor={false}
              content={<ChartTooltipContent indicator="line" />}
            />
            <Bar dataKey="mb" fill="var(--color-mb)">
              <LabelList
                dataKey="nome"
                position="insideLeft"
                offset={8}
                className="fill-white text-xs font-medium"
                fontSize={12}
              />
              <LabelList
                dataKey="mb"
                position="right"
                offset={8}
                className="fill-foreground"
                fontSize={12}
              />
            </Bar>
          </BarChart>
        </ChartContainer>
      </CardContent>
    </Card>
  );
}
