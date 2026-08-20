import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Minus, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import TablePagination from '@/components/table-pagination';
import { show } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';

export function TabTurmas({
  params,
  turmas,
  pagination = {},
  onPageChange,
  can = {},
  anoLectivoId,
}) {
  const isEmpty = !turmas.data || turmas.data.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>
          Turmas ({params.cursoTutelado.contadores?.turmas ?? 0})
        </CardTitle>
        <CardDescription>Turmas deste curso</CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhuma turma cadastrada"
            description="Comece adicionando a primeira turma clicando em uma classe acima"
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Classe</TableHead>
                <TableHead>Turno</TableHead>
                <TableHead>Max. Alunos</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {turmas.data.map((turma) => (
                <TableRow
                  key={turma.id}
                  className="hover:cursor-pointer"
                  onClick={() =>
                    router.visit(
                      show(
                        {
                          ...params,
                          cursoClasse: turma.classe.id,
                          cursoClasseTurno: turma.curso_classe_turno_id,
                          turma: turma.id,
                        },
                        {
                          query: { ano_lectivo_id: anoLectivoId },
                        },
                      ).url,
                    )
                  }
                >
                  <TableCell className="px-4 font-medium">
                    {turma.nome}
                  </TableCell>

                  <TableCell>
                    {turma.classe?.nome ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell>
                    {turma.turno?.nome ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell>
                    {turma.max_alunos ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell className="px-4 text-right">
                    <Button
                      variant="outline"
                      size="xs"
                      className="text-[10px]"
                      onClick={(event) => {
                        event.stopPropagation();
                        router.visit(
                          show(
                            {
                              ...params,
                              cursoClasse: turma.classe.id,
                              cursoClasseTurno: turma.curso_classe_turno_id,
                              turma: turma.id,
                            },
                            {
                              query: {
                                ano_lectivo_id: anoLectivoId,
                              },
                            },
                          ).url,
                        );
                      }}
                    >
                      Ver turma
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
