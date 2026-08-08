import { ArrowRightIcon, ArrowUpRight, CircleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemMedia,
  ItemTitle,
} from '@/components/ui/item';

export function HistoricoPendenteAlert({
  aluno,
  pendentes = [],
  abrirSelecaoFn,
}) {
  const classes = pendentes.map((p) => p.classe).join(', ');

  return (
    <Item variant="outline" className="" size={'sm'}>
      <ItemMedia variant="icon">
        <CircleAlert className="text-yellow-600 dark:text-yellow-400" />
      </ItemMedia>

      <ItemContent>
        <ItemTitle className="text-sm">Histórico escolar incompleto</ItemTitle>
        <ItemDescription className="">
          Faltam notas para a <span className="font-medium">{classes}</span>{' '}
          {/*para
          emitir o certificado.*/}
        </ItemDescription>
      </ItemContent>

      <ItemActions>
        <Button
          size="sm"
          variant="outline"
          className=""
          onClick={(e) => {
            e.stopPropagation();
            abrirSelecaoFn(aluno, e);
          }}
        >
          Lançar histórico
          <ArrowUpRight className="size-3.5" />
        </Button>
      </ItemActions>
    </Item>
  );
}
