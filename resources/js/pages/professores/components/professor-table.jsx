import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';

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

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

import {
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/ProfessorController';
import TablePagination from '@/components/table-pagination';

export function ProfessorTable({
  professores,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const isEmpty = !professores || professores.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Lista de professores cadastrados</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={create().url}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum professor cadastrado"
            description="Comece adicionando a primeiro professor à tabela"
            action={{
              label: 'Adicionar Professor',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Telefone</TableHead>
                 <TableHead className="px-4">Especialidade</TableHead>
                 <TableHead className="px-4">Nível Académico</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {professores.data.map((professor) => (
                <TableRow
                  key={professor.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(show(professor.id).url)}
                >
                  <TableCell className="px-4 font-medium">
                    {professor.user.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {professor.user.telefone}
                  </TableCell>

                  <TableCell className="px-4">
                  {professor.especialidade || '-'}
                </TableCell>

                <TableCell className="px-4">
                  {professor.nivel_academico || '-'}
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
                            router.visit(edit(professor.id).url);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(professor.id);
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
