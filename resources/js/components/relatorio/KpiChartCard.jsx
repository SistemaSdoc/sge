import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const DONUT_COLORS = ['#00225a', '#faa106', '#05ba7d', '#ef4444', '#8b5cf6'];

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
export function KpiChartCard({ titulo, descricao, valor, extra, chart, legend, className }) {
  return (
    <Card className={cn('gap-3', className)}>
      <CardHeader className="flex-row items-center justify-between">
        <CardTitle className="font-normal text-muted-foreground">{titulo}</CardTitle>
        {extra}
      </CardHeader>
      <CardContent className="flex flex-col gap-2">
        <span className="font-heading text-2xl font-semibold text-foreground">{valor}</span>
        {chart ? <div className="h-40 w-full">{chart}</div> : null}
        {legend}
      </CardContent>
    </Card>
  );
}

/**
 * ChartLegend — lista de legenda por baixo do gráfico: quadrado de cor + rótulo
 * à esquerda, valor + percentagem à direita. As cores TÊM de ser as mesmas
 * (e na mesma ordem) que passaste ao MiniDonutChart/MiniBarChart, para a
 * legenda bater certo com as fatias/barras.
 *
 * data: [{ label: 'Professores', valor: 4 }, ...]
 */
export function ChartLegend({ data, dataKey = 'valor', categoryKey = 'label', colors = DONUT_COLORS }) {
  if (!data?.length) return null;

  const total = data.reduce((acc, item) => acc + (item[dataKey] || 0), 0);

  return (
    <ul className="mt-1 flex flex-col gap-1.5 text-xs">
      {data.map((item, i) => {
        const valor = item[dataKey];
        const percentagem = total ? ((valor / total) * 100).toFixed(1) : '0.0';

        return (
          <li key={item[categoryKey]} className="flex items-center justify-between gap-2">
            <span className="flex items-center gap-1.5 text-muted-foreground">
              <span
                className="h-2.5 w-2.5 shrink-0 rounded-[2px]"
                style={{ backgroundColor: colors[i % colors.length] }}
              />
              {item[categoryKey]}
            </span>
            <span className="flex items-center gap-2 font-mono text-foreground tabular-nums">
              <span>{valor}</span>
              <span className="text-muted-foreground">{percentagem}%</span>
            </span>
          </li>
        );
      })}
    </ul>
  );
}

/**
 * MiniTooltip — tooltip partilhado por todos os mini-gráficos abaixo.
 * Lê o rótulo e o valor directamente do ponto de dados (categoryKey/dataKey),
 * por isso funciona da mesma forma em barras, linha, donut e barras horizontais.
 *
 * @param {(valor: number) => string} formatValor - Formata o valor no tooltip.
 *   Por omissão mostra o número simples (com separador de milhar).
 *   Passa uma função para mostrar algo específico, ex:
 *   formatValor={(v) => `${v}% de vaga ocupada`}
 */
function MiniTooltip({
  active,
  payload,
  categoryKey = 'label',
  dataKey = 'valor',
  formatValor = (valor) => (typeof valor === 'number' ? valor.toLocaleString() : valor),
}) {
  if (!active || !payload?.length) return null;

  const item = payload[0];
  const rotulo = item.payload?.[categoryKey];
  const valor = item.payload?.[dataKey];
  const cor = item.color ?? item.payload?.fill ?? item.fill;

  return (
    <div className="rounded-none border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl">
      <div className="font-medium text-foreground">{rotulo}</div>
      <div className="mt-0.5 flex items-center gap-1.5">
        <span className="h-2 w-2 shrink-0 rounded-[2px]" style={{ backgroundColor: cor }} />
        <span className="font-mono font-medium text-foreground tabular-nums">
          {formatValor(valor)}
        </span>
      </div>
    </div>
  );
}

/**
 * MiniBarChart — barras verticais por categoria (ex: alunos por classe, ocupação por classe)
 * data: [{ label: '10ª', valor: 420 }, ...]
 *
 * @param {(valor: number) => string} formatValor - ex: (v) => `${v}% de vaga ocupada`
 */
export function MiniBarChart({
  data,
  dataKey = 'valor',
  categoryKey = 'label',
  color = '#032050',
  formatValor,
}) {
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
        <Tooltip
          content={<MiniTooltip categoryKey={categoryKey} dataKey={dataKey} formatValor={formatValor} />}
          cursor={{ fill: 'var(--muted)' }}
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
export function MiniDonutChart({
  data,
  dataKey = 'valor',
  categoryKey = 'label',
  colors = DONUT_COLORS,
  formatValor,
}) {
  if (!data?.length) return <SemDados />;

  return (
    <ResponsiveContainer width="100%" height="100%">
      <PieChart>
        <Tooltip content={<MiniTooltip categoryKey={categoryKey} dataKey={dataKey} formatValor={formatValor} />} />
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
export function MiniLineChart({
  data,
  dataKey = 'valor',
  categoryKey = 'label',
  color = '#062a63',
  formatValor,
}) {
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
        <Tooltip
          content={<MiniTooltip categoryKey={categoryKey} dataKey={dataKey} formatValor={formatValor} />}
          cursor={{ stroke: color, strokeWidth: 1, strokeDasharray: '3 3' }}
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
export function MiniHorizontalBarChart({
  data,
  dataKey = 'valor',
  categoryKey = 'label',
  color = '#8b5cf6',
  formatValor,
}) {
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
        <Tooltip
          content={<MiniTooltip categoryKey={categoryKey} dataKey={dataKey} formatValor={formatValor} />}
          cursor={{ fill: 'var(--muted)' }}
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