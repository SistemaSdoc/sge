import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
  Item,
  ItemContent,
  ItemTitle,
  ItemDescription,
  ItemMedia,
  ItemActions,
} from '@/components/ui/item';
import { getSeverityConfig } from '@/utils/urgency';

export function ActionFeedItem({ item }) {
  const { color, badge } = getSeverityConfig(item.severity);

  return (
    <Item variant="outline" className="transition-colors hover:bg-accent/50">
      <ItemMedia variant="default">
        <div className={`h-8 w-1 shrink-0 rounded-full ${color}`} />
      </ItemMedia>

      <ItemContent className="min-w-0 flex-1">
        <ItemTitle className="text-sm">{item.title}</ItemTitle>

        {item.description && (
          <ItemDescription className="text-xs">
            {item.description}
          </ItemDescription>
        )}
      </ItemContent>

      <ItemActions>
        <Badge variant="secondary" className={`shrink-0 text-xs ${badge}`}>
          {item.count}
        </Badge>

        <Link
          href={item.href}
          className="flex h-6 w-6 items-center justify-center rounded-md hover:bg-accent"
        >
          <ChevronRight className="h-4 w-4" />
        </Link>
      </ItemActions>
    </Item>
  );
}
