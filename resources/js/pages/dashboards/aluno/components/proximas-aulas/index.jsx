import { ItemGroup } from '@/components/ui/item';
import { AulaItem } from './item';

export function ProximasAulas({ data = [] }) {
  if (!data.length) {
    return (
      <p className="py-8 text-center text-xs text-muted-foreground">
        Sem aulas hoje
      </p>
    );
  }

  return (
    <ItemGroup>
      {data.map((aula) => (
        console.log('aula: ', aula),
        <AulaItem key={`${aula.id}-${aula.dia}`} aula={aula} />
      ))}
    </ItemGroup>
  );
}
