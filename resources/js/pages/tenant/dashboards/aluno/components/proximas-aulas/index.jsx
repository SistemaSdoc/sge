import { ItemGroup } from '@/components/ui/item';
import { AulaItem } from './item';
import { EmptyState } from '@/components/empty-state';
import { BookText } from 'lucide-react';

export function ProximasAulas({ data = [] }) {
  if (!data.length) {
    return (
      <div className="overflow-hidden">
        <EmptyState
          icon={BookText}
          title="Sem aulas hoje"
          description="Não há aulas agendadas para os próximos dias."
          variant="table"
        />
      </div>
    );
  }

  return (
    <ItemGroup>
      {data.map((aula) => (
        <AulaItem key={`${aula.id}-${aula.dia}`} aula={aula} />
      ))}
    </ItemGroup>
  );
}
