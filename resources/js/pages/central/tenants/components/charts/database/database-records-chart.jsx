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
import { showTablesRecords } from '@/actions/App/Http/Controllers/Central/TenantController';
import { router } from '@inertiajs/react';

const chartConfig = {
  registos: {
    label: 'Registos',
    color: 'var(--color-info)',
  },
  label: {
    color: 'var(--background)',
  },
};

export function DatabaseRecordsChart({ tenant, tabelas = [] }) {
  const data = tabelas.map((t) => ({
    nome: t.nome,
    registos: t.registos,
  }));

  return (
    <Card>
      <CardHeader className="border-b">
        <CardTitle>Registos por tabela</CardTitle>
        <CardDescription>Top 5 tabelas com mais registos</CardDescription>
        <CardAction>
          <Button
            variant="outline"
            size="sm"
            className="w-full justify-center sm:w-auto"
            onClick={() =>
              router.visit(showTablesRecords({ tenant: tenant.id }).url)
            }
          >
            Ver todos os registos
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
            <Bar dataKey="registos" fill="var(--color-registos)">
              <LabelList
                dataKey="nome"
                position="insideLeft"
                offset={8}
                className="fill-white text-xs font-medium"
                fontSize={12}
              />
              <LabelList
                dataKey="registos"
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
