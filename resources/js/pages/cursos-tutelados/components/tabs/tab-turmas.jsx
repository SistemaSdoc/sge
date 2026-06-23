import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontalIcon, Minus, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import TablePagination from '@/components/table-pagination';
import { show } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export function TabTurmas({
  turmas,
  pagination = {},
  onPageChange,
  instituicaoId,
  cursoTuteladoId,
}) {
  const isEmpty = !turmas.data || turmas.data.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Turmas</CardTitle>
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
                      show({
                        instituicao: instituicaoId,
                        cursoTutelado: cursoTuteladoId,
                        cursoClasse: turma.classe.id,
                        cursoClasseTurno: turma.curso_classe_turno_id,
                        turma: turma.id,
                      }).url,
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
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(event) => {
                            event.stopPropagation();
                            router.visit(
                              show({
                                instituicao: instituicaoId,
                                cursoTutelado: cursoTuteladoId,
                                cursoClasse: turma.classe.id,
                                cursoClasseTurno: turma.curso_classe_turno_id,
                                turma: turma.id,
                              }).url,
                            );
                          }}
                        >
                          Ver turma
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem variant="destructive" disabled>
                          Remover
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
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
