import { ItemGroup } from '@/components/ui/item';
import { AvisoEventoItem } from './item';

export function AvisosEventos({ data = [] }) {
  if (!data.length) {
    return (
      <p className="py-8 text-center text-xs text-muted-foreground">
        Sem avisos ou eventos
      </p>
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
