import { EmptyState } from './empty-state';

export function BarList({ items = [], emptyText = 'Sem dados' }) {
  if (!items.length) {
    return <EmptyState text={emptyText} />;
  }

  const max = Math.max(1, ...items.map((item) => item.value));

  return (
    <div className="flex h-full min-h-64 flex-col justify-center">
      <ul className="w-full space-y-4">
        {items.map((item) => (
          <li key={item.label}>
            <div className="mb-1.5 flex justify-between text-sm">
              <span className="text-muted-foreground">{item.label}</span>
              <span className="font-semibold">{item.value}</span>
            </div>
            <div className="h-2 bg-muted">
            <div className="h-full" style={{ width: `${(item.value / max) * 100}%`, background: item.color ?? 'var(--color-info)' }} />
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}