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
  show,
  create,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/ClasseController';
import TablePagination from '@/components/table-pagination';

export function ClasseTable({
  classes,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const lista = classes?.data ?? [];
  const isEmpty = lista.length === 0;
  const hasActionColumn = lista.some(
    (classe) => classe.can?.edit_classe || classe.can?.delete_classe,
  );

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Classes</CardTitle>
          <CardDescription>Lista de classes cadastradas</CardDescription>
          <CardAction>
            {can.create_classe && (
              <Button asChild>
                <Link href={create().url}>Adicionar</Link>
              </Button>
            )}
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title="Nenhuma classe cadastrada"
              description="Comece adicionando a primeira classe à tabela"
              action={
                can.create_classe
                  ? {
                      label: 'Adicionar Classe',
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
                  <TableHead className="px-4">Nível de Ensino</TableHead>

                  {hasActionColumn && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>

              <TableBody>
                {lista.map((classe) => (
                  <TableRow
                    key={classe.id}
                    className={
                      classe.can?.view_classe
                        ? 'hover:cursor-pointer'
                        : 'opacity-70'
                    }
                    onClick={() => {
                      if (classe.can?.view_classe) {
                        router.visit(show(classe.id).url);
                      }
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {classe.nome}
                    </TableCell>

                    <TableCell className="px-4">
                      {classe.nivel_ensino}
                    </TableCell>

                    {hasActionColumn && (
                      <TableCell className="px-4 text-right">
                        {(classe.can?.edit_classe ||
                          classe.can?.delete_classe) && (
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
                              {classe.can?.edit_classe && (
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    router.visit(edit(classe.id).url);
                                  }}
                                >
                                  Editar
                                </DropdownMenuItem>
                              )}

                              {classe.can?.edit_classe &&
                                classe.can?.delete_classe && (
                                  <DropdownMenuSeparator />
                                )}

                              {classe.can?.delete_classe && (
                                <DropdownMenuItem
                                  variant="destructive"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    deleteFn(classe.id);
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
