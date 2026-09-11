import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  XAxis,
  YAxis,
} from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const DONUT_COLORS = ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'];

/**
 * KpiChartCard
 * Card de KPI reutilizável: valor grande + mini-gráfico opcional em baixo.
 * Usa os mesmos tokens visuais do resto da app (Card rounded-none, ring-foreground/10).
 *
 * @param {string} titulo - Título do card (ex: "Alunos")
 * @param {string|number} valor - Valor principal em destaque
 * @param {React.ReactNode} extra - Conteúdo opcional no canto superior direito (badge, menu, etc)
 * @param {React.ReactNode} chart - Um dos mini-gráficos abaixo (MiniBarChart, MiniDonutChart, ...)
 * @param {string} className - Classes adicionais
 */
export function KpiChartCard({ titulo, valor, extra, chart, className }) {
  return (
    <Card className={cn('gap-3', className)}>
      <CardHeader className="flex-row items-center justify-between">
        <CardTitle className="font-normal text-muted-foreground">{titulo}</CardTitle>
        {extra}
      </CardHeader>
      <CardContent className="flex flex-col gap-2">
        <span className="font-heading text-2xl font-semibold text-foreground">{valor}</span>
        {chart ? <div className="h-16 w-full">{chart}</div> : null}
      </CardContent>
    </Card>
  );
}

/**
 * MiniBarChart — barras verticais por categoria (ex: alunos por classe, ocupação por classe)
 * data: [{ label: '10ª', valor: 420 }, ...]
 */
export function MiniBarChart({ data, dataKey = 'valor', categoryKey = 'label', color = '#3b82f6' }) {
  if (!data?.length) return <SemDados />;

  return (
    <ResponsiveContainer width="100%" height="100%">
      <BarChart data={data} margin={{ top: 4, right: 0, left: 0, bottom: 0 }}>
        <XAxis
          dataKey={categoryKey}
          tick={{ fontSize: 10 }}
          axisLine={false}
          tickLine={false}
          interval={0}
        />
        <Bar dataKey={dataKey} fill={color} radius={0} />
      </BarChart>
    </ResponsiveContainer>
  );
}

/**
 * MiniDonutChart — distribuição por estado/categoria (ex: professores por especialidade, PAP por estado)
 * data: [{ label: 'Aprovado', valor: 12 }, ...]
 */
export function MiniDonutChart({ data, dataKey = 'valor', colors = DONUT_COLORS }) {
  if (!data?.length) return <SemDados />;

  return (
    <ResponsiveContainer width="100%" height="100%">
      <PieChart>
        <Pie data={data} dataKey={dataKey} innerRadius="60%" outerRadius="90%" paddingAngle={2} stroke="none">
          {data.map((_, i) => (
            <Cell key={i} fill={colors[i % colors.length]} />
          ))}
        </Pie>
      </PieChart>
    </ResponsiveContainer>
  );
}

/**
 * MiniLineChart — evolução ao longo do tempo (ex: receita mensal)
 * data: [{ label: 'Jan', valor: 125000 }, ...]
 */
export function MiniLineChart({ data, dataKey = 'valor', categoryKey = 'label', color = '#3b82f6' }) {
  if (!data?.length) return <SemDados />;

  const gradientId = `mini-line-fill-${dataKey}`;

  return (
    <ResponsiveContainer width="100%" height="100%">
      <AreaChart data={data} margin={{ top: 4, right: 0, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={color} stopOpacity={0.25} />
            <stop offset="100%" stopColor={color} stopOpacity={0} />
          </linearGradient>
        </defs>
        <XAxis
          dataKey={categoryKey}
          tick={{ fontSize: 10 }}
          axisLine={false}
          tickLine={false}
          interval={0}
        />
        <Area type="monotone" dataKey={dataKey} stroke={color} strokeWidth={2} fill={`url(#${gradientId})`} />
      </AreaChart>
    </ResponsiveContainer>
  );
}

/**
 * MiniHorizontalBarChart — ranking curto por categoria (ex: documentos por tipo)
 * data: [{ label: 'Certificados', valor: 12 }, ...]
 */
export function MiniHorizontalBarChart({ data, dataKey = 'valor', categoryKey = 'label', color = '#8b5cf6' }) {
  if (!data?.length) return <SemDados />;

  return (
    <ResponsiveContainer width="100%" height="100%">
      <BarChart data={data} layout="vertical" margin={{ top: 0, right: 8, left: 0, bottom: 0 }}>
        <XAxis type="number" hide />
        <YAxis
          type="category"
          dataKey={categoryKey}
          width={72}
          tick={{ fontSize: 10 }}
          axisLine={false}
          tickLine={false}
        />
        <Bar dataKey={dataKey} fill={color} radius={0} barSize={10} />
      </BarChart>
    </ResponsiveContainer>
  );
}

function SemDados() {
  return (
    <div className="flex h-full items-center text-[11px] text-muted-foreground">
      Sem dados ainda.
    </div>
  );
}