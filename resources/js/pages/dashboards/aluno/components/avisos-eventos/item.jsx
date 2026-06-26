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

const TYPE_CONFIG = {
  aviso: {
    label: 'Aviso',
    color:
      'bg-blue-100 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900/30',
    badgeVariant: 'secondary',
  },
  evento: {
    label: 'Evento',
    color:
      'bg-purple-100 dark:bg-purple-950/20 border-purple-200 dark:border-purple-900/30',
    badgeVariant: 'secondary',
  },
  urgente: {
    label: 'Urgente',
    color:
      'bg-red-100 dark:bg-red-950/20 border-red-200 dark:border-red-900/30',
    badgeVariant: 'destructive',
  },
  default: {
    label: 'Info',
    color:
      'bg-gray-100 dark:bg-gray-950/20 border-gray-200 dark:border-gray-900/30',
    badgeVariant: 'secondary',
  },
};

export function AvisoEventoItem({ item }) {
  const { label, color, badgeVariant } =
    TYPE_CONFIG[item.type] ?? TYPE_CONFIG.default;

  return (
    <Item
      variant="outline"
      className={`${color} border transition-opacity hover:opacity-80`}
    >
      <ItemContent className="flex-1">
        <ItemTitle>
          {item.titulo}

          {item.data && (
            <span className="mt-1 block text-xs text-muted-foreground">
              — {format(new Date(item.data), 'd MMM • HH:mm', { locale: pt })}
            </span>
          )}
        </ItemTitle>

        {item.descricao && (
          <ItemDescription className="line-clamp-2 text-xs">
            {item.descricao}
          </ItemDescription>
        )}
      </ItemContent>

      <ItemActions>
        <Badge variant={badgeVariant} className="shrink-0 text-xs">
          {label}
        </Badge>
      </ItemActions>
    </Item>
  );
}
