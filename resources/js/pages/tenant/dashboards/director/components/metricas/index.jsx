import { MetricItem } from './item';
import { METRICS_CONFIG } from '@/utils/metrics';

export function MetricsBar({ metrics }) {
  if (!metrics) return null;

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
      {METRICS_CONFIG.map(({ id, label, href }) => (
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
