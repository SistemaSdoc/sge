import { Item, ItemContent } from '@/components/ui/item';

export function SummaryItem({ className, children }) {
  return (
    <Item variant="outline" className={className}>
      <ItemContent>
        <p className="text-xs">{children}</p>
      </ItemContent>
    </Item>
  );
}
