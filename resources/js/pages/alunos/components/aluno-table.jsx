import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Filter, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
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
  DropdownMenuSeparator,
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
import TablePagination from '@/components/table-pagination';

export function AlunoTable({ data, deleteFn, pagination = {}, onPageChange }) {
  const isEmpty = !data || data.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Alunos</CardTitle>
        <CardDescription>Lista de alunos cadastrados</CardDescription>
        <CardAction className="flex gap-3">
          <Field>
            <div className="flex gap-2">
              <Input placeholder="Digite para pesquisar..." />
              <Button variant="outline">Pesquisar</Button>
            </div>
          </Field>
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
            title="Nenhum aluno cadastrado"
            description="Comece adicionando o primeiro aluno à tabela"
            action={{
              label: 'Adicionar Aluno',
              href: '/alunos/create',
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Curso</TableHead>
                <TableHead className="px-4">Turno</TableHead>
                <TableHead className="px-4">Turma</TableHead>
                <TableHead className="px-4">Classe</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.map((aluno) => (
                <TableRow
                  key={aluno.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(`/alunos/${aluno.id}`)}
                >
                  <TableCell className="px-4 font-medium">
                    {aluno.nome}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {aluno.curso}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {aluno.turno}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {aluno.turma}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {aluno.classe}
                  </TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="size-8"
                          onClick={(e) => e.stopPropagation()}
                          onPointerDown={(e) => e.stopPropagation()}
                        >
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(`/alunos/${aluno.id}/edit`);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(aluno.id);
                          }}
                        >
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
