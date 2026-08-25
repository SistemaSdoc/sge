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
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';

const chartConfig = {
  registos: {
    label: 'Registos',
    color: 'var(--color-info)',
  },
  label: {
    color: 'var(--background)',
  },
};

export function DatabaseRecordsChart({ tenant, registos = [] }) {
  // Filtra tabelas com 0 registos
  const filtered = registos.filter((t) => t.registos > 0);

  const data = filtered.map((t) => ({
    nome: t.nome,
    registos: t.registos,
  }));

  // Altura dinâmica: ~18px por barra
  const chartHeight = Math.max(300, data.length * 18);

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>Registos por tabela</CardTitle>
          <CardDescription>
            Total de linhas nas tabelas da base de dados do{' '}
            <span className="font-semibold">{tenant.instituicao.nome}</span>
          </CardDescription>
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
    </div>
  );
}
