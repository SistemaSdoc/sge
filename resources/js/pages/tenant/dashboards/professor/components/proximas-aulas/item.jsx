import { Badge } from '@/components/ui/badge';
import {
  Item,
  ItemContent,
  ItemTitle,
  ItemDescription,
  ItemActions,
} from '@/components/ui/item';
import { getBadgeVariantForDay, getDayCategory } from '@/utils/get-badge-color';

export function AulaItem({ aula }) {
  const diaCategory = getDayCategory(aula.dia);

  return (
    <Item variant="outline">
      <ItemContent className="flex-1">
        <ItemTitle>{aula.disciplina.nome}</ItemTitle>

        <ItemDescription>{aula.turma.nome} — Sala X</ItemDescription>
      </ItemContent>

      <ItemActions>
        <Badge className={getBadgeVariantForDay(diaCategory)}>
          {aula.dia_label ?? diaCategory} • {aula.horario.hora_inicio} às{' '}
          {aula.horario.hora_fim}
        </Badge>
      </ItemActions>
    </Item>
  );
}
