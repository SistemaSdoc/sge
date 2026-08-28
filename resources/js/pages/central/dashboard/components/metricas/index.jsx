import { MetricItem } from '@/pages/tenant/dashboards/director/components/metricas/item';
import { METRICAS_CONFIG } from '@/utils/central-static';

export function MetricsBar({ metrics = {} }) {
  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      {METRICAS_CONFIG.map(({ id, label, href }) => (
        <MetricItem
          key={id}
          label={label}
          value={metrics[id] ?? 0}
          href={href}
        />
      ))}
    </div>
  );
}
