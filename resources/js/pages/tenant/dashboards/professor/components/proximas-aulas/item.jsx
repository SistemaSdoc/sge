import { Badge } from '@/components/ui/badge';
import {
  Item,
  ItemContent,
  ItemTitle,
  ItemDescription,
  ItemActions,
} from '@/components/ui/item';
import { getBadgeVariantForDay, getDayCategory, isAulaHappening } from '@/utils/get-badge-color';

export function AulaItem({ aula }) {
  const happening = isAulaHappening(aula.dia, aula.horario);
  const diaCategory = happening ? 'today' : getDayCategory(aula.dia);

  return (
    <Item variant="outline">
      <ItemContent className="flex-1">
        <ItemTitle>{aula.disciplina.nome}</ItemTitle>
        <ItemDescription>Turma: {aula.turma.nome}</ItemDescription>
      </ItemContent>
      <ItemActions>
        <Badge className={getBadgeVariantForDay(diaCategory)}>
          {happening ? 'A decorrer' : (aula.dia_label ?? diaCategory)} •{' '}
          {aula.horario.hora_inicio} às {aula.horario.hora_fim}
        </Badge>
      </ItemActions>
    </Item>
  );
}
