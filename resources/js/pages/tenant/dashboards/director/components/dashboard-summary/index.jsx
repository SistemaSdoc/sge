import { Item, ItemMedia, ItemContent } from '@/components/ui/item';
import { CheckCircle2 } from 'lucide-react';
import { getUrgencyConfig } from '@/utils/urgency';

export function DashboardSummary({ items = [] }) {
  if (!items.length) {
    return (
      <Item
        variant="outline"
        className="border-emerald-200 bg-emerald-50 dark:border-emerald-900/30 dark:bg-emerald-950/20"
      >
        <ItemMedia variant="icon">
          <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
        </ItemMedia>

        <ItemContent>
          <p className="text-xs text-emerald-700 dark:text-emerald-300">
            <span className="font-semibold">Excelente!</span> Nenhum item
            pendente.
          </p>
        </ItemContent>
      </Item>
    );
  }

  const { Icon, ...style } = getUrgencyConfig(items);

  const total = items.reduce((a, i) => a + i.count, 0);

  return (
    <Item variant="outline" className={`${style.borderClass} ${style.bgClass}`}>
      <ItemMedia variant="icon">
        <Icon className={`size-4 ${style.iconClass}`} />
      </ItemMedia>

      <ItemContent>
        <p className={`text-xs ${style.textClass}`}>
          <span className="font-semibold">{style.label}</span> Tens {total}{' '}
          {total === 1 ? 'item pendente' : 'itens pendentes'} que{' '}
          {total === 1 ? 'requer' : 'requerem'} atenção.
        </p>
      </ItemContent>
    </Item>
  );
}
