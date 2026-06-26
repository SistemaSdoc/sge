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
  const diaCategory = getDayCategory(aula?.dia);
  const badgeStyles = getBadgeVariantForDay(diaCategory);

  return (
    <Item key={`${aula?.id}-${aula?.dia}`} variant="outline">
      <ItemContent className="flex-1">
        <ItemTitle>
          {aula?.disciplina?.sigla} - {aula?.disciplina?.nome}
        </ItemTitle>
        <ItemDescription>{aula?.professor?.nome}</ItemDescription>
      </ItemContent>

      <ItemActions>
        <Badge className={badgeStyles}>
          {aula?.dia_label ?? diaCategory} • {aula?.horario?.hora_inicio} às{' '}
          {aula?.horario?.hora_fim}
        </Badge>
      </ItemActions>
    </Item>
  );
}
