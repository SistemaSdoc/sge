import { Badge } from '@/components/ui/badge';
import {
  Item,
  ItemContent,
  ItemTitle,
  ItemDescription,
  ItemActions,
} from '@/components/ui/item';
import { format } from 'date-fns';
import { pt } from 'date-fns/locale';
import { getEventType } from '@/utils/urgency';

export function ProximoEventoItem({ event }) {
  const { label, badge } = getEventType(event.type);

  return (
    <Item variant="outline">
      <ItemContent className="flex-1">
        <ItemTitle>{format(event.date, 'd MMM', { locale: pt })}</ItemTitle>

        <ItemDescription>{event.title}</ItemDescription>
      </ItemContent>

      <ItemActions>
        <Badge variant="secondary" className={badge}>
          {label}
        </Badge>
      </ItemActions>
    </Item>
  );
}
