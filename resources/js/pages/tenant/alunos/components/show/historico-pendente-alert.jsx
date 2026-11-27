import { CircleAlert } from 'lucide-react';
import { router } from '@inertiajs/react';
import { challenge } from '@/routes/tenant/dashboard/notes/two-factor';
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
  iniciarHistoricoFn,
}) {
  const nomes = pendentes.map((p) => p.classe).join(', ');
  const emCurso = pendentes.filter((p) => p.em_curso); // pode haver vários
  const semTurma = pendentes.filter((p) => !p.em_curso);

  return (
    <Item variant="outline" size="sm">
      <ItemMedia variant="icon">
        <CircleAlert className="text-yellow-600 dark:text-yellow-400" />
      </ItemMedia>

      <ItemContent>
        <ItemTitle className="text-sm">Histórico escolar incompleto</ItemTitle>
        <ItemDescription>
          Faltam notas para a <span className="font-medium">{nomes}</span>
        </ItemDescription>
      </ItemContent>

      <ItemActions className="flex flex-wrap gap-2">
        {/* Um botão por cada classe em curso */}
        {emCurso.map((pendente) => (
          <Button
            key={pendente.turma_aluno_id}
            size="sm"
            onClick={() =>
              router.visit(
                `/dashboard/historico/${aluno.id}/lancar?turma_aluno_id=${pendente.turma_aluno_id}`,
              )
            }
          >
            {pendente.tem_notas ? 'Continuar' : 'Lançar Notas'} —{' '}
            {pendente.classe}
          </Button>
        ))}

        {/* Classes que ainda não têm turma_aluno */}
        {semTurma.length > 0 && (
          <Button
            size="sm"
            variant="outline"
            onClick={() => {
              const returnTo = new URL(window.location.href);
              returnTo.searchParams.set('iniciar_historico', '1');

              iniciarHistoricoFn(
                challenge.url({
                  query: {
                    scope: 'history',
                    return_to: `${returnTo.pathname}${returnTo.search}`,
                  },
                }),
              );
            }}
          >
            Iniciar lançamento
            {semTurma.length > 1
              ? ` (${semTurma.length} classes)`
              : ` — ${semTurma[0].classe}`}
          </Button>
        )}
      </ItemActions>
    </Item>
  );
}
