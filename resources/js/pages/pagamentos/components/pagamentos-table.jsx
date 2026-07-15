import { Link, router } from '@inertiajs/react';
import { LayersIcon, MoreHorizontalIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import TablePagination from '@/components/table-pagination';
import { create, edit } from '@/actions/App/Http/Controllers/AvisoController';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);

  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MZN`;
};

export default function PagamentosTable({
  pagamentos = [],
  can,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const hasAnyAction = pagamentos.some(
    (pagamento) => pagamento.can?.update || pagamento.can?.delete,
  );
  const isEmpty = !pagamentos || pagamentos.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Pagamentos</CardTitle>
        <CardDescription>
          Registos de propinas e outros encargos escolares.
        </CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={'/dashboard/pagamentos/create'}>Registar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum pagamento registado"
            description="Comece por adicionar o primeiro pagamento à tabela."
            action={
              can?.create
                ? {
                    label: 'Adicionar pagamento',
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
                <TableHead className="px-4">Estudante</TableHead>
                <TableHead className="px-4">Referência</TableHead>
                <TableHead className="px-4">Valor</TableHead>
                <TableHead className="px-4">Estado</TableHead>
                <TableHead className="px-4">Data</TableHead>
                {hasAnyAction && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {pagamentos.map((pagamento) => (
                <TableRow key={pagamento.id}>
                  <TableCell className="px-4 font-medium">
                    {pagamento.estudante ?? pagamento.aluno ?? '—'}
                  </TableCell>

                  <TableCell className="px-4 text-muted-foreground">
                    {pagamento.referencia ?? pagamento.mes ?? '—'}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {formatCurrency(pagamento.valor)}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {pagamento.estado ?? 'pendente'}
                  </TableCell>

                  <TableCell className="px-4 text-muted-foreground">
                    {pagamento.data ?? '—'}
                  </TableCell>

                  {hasAnyAction && (
                    <TableCell className="px-4 text-right">
                      {(pagamento.can?.update || pagamento.can?.delete) && (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="size-8"
                            >
                              <MoreHorizontalIcon />
                              <span className="sr-only">Abrir menu</span>
                            </Button>
                          </DropdownMenuTrigger>

                          <DropdownMenuContent align="end">
                            {pagamento.can?.update && (
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  router.visit(edit(pagamento.id).url);
                                }}
                              >
                                Editar
                              </DropdownMenuItem>
                            )}

                            {pagamento.can?.update && pagamento.can?.delete && (
                              <DropdownMenuSeparator />
                            )}

                            {pagamento.can?.delete && (
                              <DropdownMenuItem
                                variant="destructive"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  deleteFn(pagamento.id);
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
  );
}
