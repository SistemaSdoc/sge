import { Button } from '@/components/ui/button';
import { Filter, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { router } from '@inertiajs/react';

import {
  Card,
  CardAction,
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
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index } from '@/actions/App/Http/Controllers/TurmaController';
import { show } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import TablePagination from '@/components/table-pagination';

export function TurmaTable({
  turmas,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const lista = Array.isArray(turmas) ? turmas : turmas?.data ?? [];
  const isEmpty = lista.length === 0;
  const hasActionColumn = lista.some((turma) => turma.can?.view);

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Turmas</CardTitle>
        <CardDescription>Lista de turmas disponíveis</CardDescription>
        <CardAction className="flex gap-3">
          <Input placeholder="Digite para pesquisar..." />
          <Button variant="outline">
            <Filter /> Filtrar
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhuma turma associada"
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Curso</TableHead>
                <TableHead className="px-4">Classe</TableHead>
                <TableHead className="px-4">Total de Alunos</TableHead>
                {hasActionColumn && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {lista.map((turma) => (
                <TableRow
                  key={turma.id}
                  className={turma.can?.view ? 'hover:cursor-pointer' : 'opacity-70'}
                  onClick={() => {
                    if (turma.can?.view) {
                      router.visit(
                        show({
                          instituicao: turma.instituicao.id,
                          cursoTutelado: turma.curso.id,
                          cursoClasse: turma.classe.id,
                          cursoClasseTurno: turma.turno.id,
                          turma: turma.id,
                        }),
                      );
                    }
                  }}
                >
                  <TableCell className="px-4 font-medium">
                    {turma.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.curso?.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.classe?.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {turma?.total_alunos}
                  </TableCell>
                  {hasActionColumn && (
                    <TableCell className="px-4 text-right">
                      {turma.can?.view && (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-8">
                              <MoreHorizontalIcon />
                              <span className="sr-only">Open menu</span>
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              onClick={() =>
                                router.visit(
                                  show({
                                    instituicao: turma.instituicao.id,
                                    cursoTutelado: turma.curso.id,
                                    cursoClasse: turma.classe.id,
                                    cursoClasseTurno: turma.turno.id,
                                    turma: turma.id,
                                  }),
                                )
                              }
                            >
                              Ver turma
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      )}
                    </TableCell>
                  )}
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
