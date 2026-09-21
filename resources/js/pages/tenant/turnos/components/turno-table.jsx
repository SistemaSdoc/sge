import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, ClockIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import {
  Card,
  CardAction,
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/TurnoController';
import TablePagination from '@/components/table-pagination';

export function TurnoTable({
  turnos,
  can = {},
  pagination = {},
  onPageChange,
  deleteFn,
}) {
  const lista = Array.isArray(turnos) ? turnos : (turnos?.data ?? []);
  const isEmpty = lista.length === 0;
  const hasActionColumn = lista.some(
    (turno) => turno.can?.edit || turno.can?.delete,
  );

  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Turnos</CardTitle>
          <CardDescription>Lista de turnos cadastrados</CardDescription>
          <CardAction>
            {can.create && (
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
              icon={ClockIcon}
              title="Nenhum turno cadastrado"
              description="Comece adicionando o primeiro turno à tabela"
              action={
                can.create
                  ? {
                      label: 'Adicionar Turno',
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
                {lista.map((turno) => (
                  <TableRow
                    key={turno.id}
                    className={
                      turno.can?.view
                        ? 'hover:cursor-pointer'
                        : 'opacity-70'
                    }
                    onClick={() => {
                      if (turno.can?.view) {
                        router.visit(show(turno.id).url);
                      }
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {turno.nome}
                    </TableCell>
                    {hasActionColumn && (
                      <TableCell className="px-4 text-right">
                        {(turno.can?.edit || turno.can?.delete) && (
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
                              {turno.can?.edit && (
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    router.visit(edit(turno.id).url);
                                  }}
                                >
                                  Editar
                                </DropdownMenuItem>
                              )}

                              {turno.can?.edit && turno.can?.delete && (
                                  <DropdownMenuSeparator />
                                )}

                              {turno.can?.delete && (
                                <DropdownMenuItem
                                  variant="destructive"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    deleteFn(turno.id);
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
