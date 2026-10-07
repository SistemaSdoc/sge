import { ItemGroup } from '@/components/ui/item';
import { AvisoEventoItem } from './item';
import { EmptyState } from '@/components/empty-state';
import { Calendar1 } from 'lucide-react';

export function AvisosEventos({ data = [] }) {
  if (!data.length) {
    return (
      <div className="overflow-hidden">
        <EmptyState
          icon={Calendar1}
          title="Sem avisos ou eventos"
          description="Não há avisos ou eventos por visualizar."
          variant="table"
        />
      </div>
    );
  }

  return (
    <ItemGroup>
      {data.map((item) => (
        <AvisoEventoItem key={item.id} item={item} />
      ))}
    </ItemGroup>
  );
}
