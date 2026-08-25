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
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowUpRight } from 'lucide-react';
import { showTablesSize } from '@/actions/App/Http/Controllers/Central/TenantController';
import { router } from '@inertiajs/react';

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

  return (
    <Card>
      <CardHeader className="border-b">
        <CardTitle>Tamanho das tabelas</CardTitle>
        <CardDescription>Top 5 tabelas mais pesadas</CardDescription>

        <CardAction>
          <Button
            variant="outline"
            size="sm"
            className="w-full justify-center sm:w-auto"
            onClick={() =>
              router.visit(showTablesSize({ tenant: tenant.id }).url)
            }
          >
            Ver todas as tabelas
            <ArrowUpRight className="shrink-0" />
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent>
        <ChartContainer config={chartConfig} className="h-75 w-full">
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
