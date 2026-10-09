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
import {
  show as showTurma,
  edit as editTurma,
} from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';

const EmptyCell = () => <Minus size={15} className="text-muted-foreground" />;

export function TabTurmas({
  params,
  turmas,
  pagination = {},
  onPageChange,
  anoLectivoId,
}) {
  const isEmpty = !turmas.data || turmas.data.length === 0;

  const rotaParams = (turma) => ({
    ...params,
    cursoClasse: turma.classe?.id,
    cursoClasseTurno: turma.curso_classe_turno_id,
    turma: turma.id,
  });

  const verTurma = (turma) =>
    router.visit(
      showTurma(rotaParams(turma), {
        query: { ano_lectivo_id: anoLectivoId },
      }).url,
    );

  const editarTurma = (turma) =>
    router.visit(
      editTurma(rotaParams(turma), {
        query: { origem: 'curso', ano_lectivo_id: anoLectivoId },
      }).url,
    );

  return (
    <Card className="gap-0">
      <CardHeader className="flex flex-col gap-1 border-b">
        <CardTitle>
          Turmas ({params.cursoTutelado?.contadores?.turmas ?? 0})
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
                <TableHead>Sala</TableHead>
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
                  onClick={() => verTurma(turma)}
                >
                  <TableCell className="px-4 font-medium">
                    {turma.nome}
                  </TableCell>
                  <TableCell>{turma.classe?.nome ?? <EmptyCell />}</TableCell>
                  <TableCell>{turma.sala || 'Por definir'}</TableCell>
                  <TableCell>{turma.turno?.nome ?? <EmptyCell />}</TableCell>
                  <TableCell>{turma.max_alunos ?? <EmptyCell />}</TableCell>

                  <TableCell className="px-4 text-right">
                    <div className="flex justify-end gap-2">
                      <Button
                        variant="outline"
                        size="xs"
                        className="text-[10px]"
                        onClick={(e) => {
                          e.stopPropagation();
                          verTurma(turma);
                        }}
                      >
                        Ver turma
                      </Button>

                      {turma.can?.edit && (
                        <Button
                          variant="outline"
                          size="xs"
                          className="text-[10px]"
                          onClick={(e) => {
                            e.stopPropagation();
                            editarTurma(turma);
                          }}
                        >
                          Editar
                        </Button>
                      )}
                    </div>
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
