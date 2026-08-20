import { ItemGroup } from '@/components/ui/item';
import { Calendar } from 'lucide-react';
import { ProximoEventoItem } from './item';

export function ProximosEventos({ events = [] }) {
  if (!events.length) {
    return (
      <div className="py-8 text-center text-muted-foreground">
        <Calendar className="mx-auto mb-2 size-4 opacity-50" />
        <p className="text-xs">Nenhum evento próximo</p>
      </div>
    );
  }

  return (
    <ItemGroup>
      {events.map((event) => (
        <ProximoEventoItem key={event.id} event={event} />
      ))}
    </ItemGroup>
  );
}
