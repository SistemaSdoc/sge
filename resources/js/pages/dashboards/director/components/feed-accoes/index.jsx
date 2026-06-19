import { Item, ItemGroup, ItemMedia, ItemContent } from '@/components/ui/item';
import { BadgeCheck } from 'lucide-react';
import { ActionFeedItem } from './item';

export function ActionFeed({ items = [] }) {
  if (!items.length) {
    return (
      <Item
        variant="outline"
        className="border-emerald-200/50 bg-emerald-50 dark:border-emerald-900/30 dark:bg-emerald-950/20"
      >
        <ItemMedia variant="icon">
          <BadgeCheck className="size-4 text-emerald-600 dark:text-emerald-400" />
        </ItemMedia>
        <ItemContent>
          <p className="text-xs text-emerald-700 dark:text-emerald-300">
            <span className="font-semibold">Tudo em ordem!</span> Sem acções
            pendentes.
          </p>
        </ItemContent>
      </Item>
    );
  }

  return (
    <ItemGroup>
      {items.map((item) => (
        <ActionFeedItem key={item.id} item={item} />
      ))}
    </ItemGroup>
  );
}
