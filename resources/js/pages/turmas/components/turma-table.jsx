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

export function TurmaTable({ turmas, deleteFn }) {
  const isEmpty = !turmas || turmas.length === 0;
  

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
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
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {turmas.map((turma) => (
                <TableRow
                  key={turma.id}
                  className="hover:cursor-pointer"
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
                          onClick={(e) => {
                            e.stopPropagation();
                            // navegar para a pauta desta turma quando tiveres a rota
                          }}
                        >
                          Ver detalhes
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

      {!isEmpty && (
        <CardFooter className="justify-between">
          <span className="text-muted-foreground">Página 1 de 4</span>
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious href="#" />
              </PaginationItem>
              <PaginationItem>
                <PaginationNext href="#" />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      )}
    </Card>
  );
}
