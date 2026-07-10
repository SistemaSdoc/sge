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
} from '@/actions/App/Http/Controllers/CursosController';
import TablePagination from '@/components/table-pagination';

export default function CursoTable({
  cursos,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const isEmpty = !cursos || cursos.length === 0;
  const hasActionColumn = cursos?.some(
    (curso) => curso.can?.edit_curso || curso.can?.delete_curso,
  );
  const canCreate = Boolean(can.create_curso || can.create);

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Cursos</CardTitle>
          <CardDescription>Lista de cursos cadastrados</CardDescription>
          {canCreate && (
            <CardAction>
              <Button asChild>
                <Link href={create().url}>Adicionar</Link>
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title="Nenhum curso cadastrado"
              description="Comece adicionando a primeiro curso à tabela"
              action={
                canCreate
                  ? {
                      label: 'Adicionar Curso',
                      href: create().url,
                      variant: 'outline',
                    }
                  : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  {hasActionColumn && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {cursos.map((curso) => (
                  <TableRow
                    key={curso.id}
                    className={
                      curso.can?.view_curso
                        ? 'hover:cursor-pointer'
                        : 'opacity-70'
                    }
                    onClick={() => {
                      if (curso.can?.view_curso) {
                        router.visit(show(curso.id).url);
                      }
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {curso.nome}
                    </TableCell>
                    {hasActionColumn && (
                      <TableCell className="px-4 text-right">
                        {(curso.can?.edit_curso || curso.can?.delete_curso) && (
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                              >
                                <MoreHorizontalIcon />
                                <span className="sr-only">Open menu</span>
                              </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent align="end">
                              {curso.can?.edit_curso && (
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    router.visit(edit(curso.id).url);
                                  }}
                                >
                                  Editar
                                </DropdownMenuItem>
                              )}

                              {curso.can?.edit_curso &&
                                curso.can?.delete_curso && (
                                  <DropdownMenuSeparator />
                                )}

                              {curso.can?.delete_curso && (
                                <DropdownMenuItem
                                  variant="destructive"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    deleteFn(curso.id);
                                  }}
                                >
                                  Remover
                                </DropdownMenuItem>
                              )}
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
    </div>
  );
}
