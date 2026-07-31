import { Link, router } from '@inertiajs/react';
import { LayersIcon, MoreHorizontalIcon, PlusIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import {
  create,
  show,
  destroy,
} from '@/actions/App/Http/Controllers/PagamentoController';
import { DropdownMenuSeparator } from '@radix-ui/react-dropdown-menu';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const metodoLabels = {
  dinheiro: 'Dinheiro',
  transferencia: 'Transferência',
  multicaixa: 'Multicaixa',
  outro: 'Outro',
};

export default function PagamentosTable({
  pagamentos = [],
  can,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const isEmpty = pagamentos.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Pagamentos</CardTitle>
        <CardDescription>
          Registos de propinas e outros encargos escolares.
        </CardDescription>

        <CardAction className="flex items-center gap-2">
          {can?.create && (
            <Button asChild>
              <Link href={create().url}>
                <PlusIcon className="mr-1 size-4" />
                Adicionar Pagamento
              </Link>
            </Button>
          )}
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum pagamento registado"
            description="Comece por registar o primeiro pagamento."
            action={
              can?.create
                ? {
                    label: 'Registar pagamento',
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
                <TableHead className="px-4">Aluno</TableHead>
                <TableHead className="px-4 text-center">Método</TableHead>
                <TableHead className="px-4 text-center">Total</TableHead>
                <TableHead className="px-4 text-center">Realizado em</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {pagamentos.map((p) => (
                <TableRow
                  key={p.id}
                  className="cursor-pointer"
                  onClick={() => router.visit(show(p.id).url)}
                >
                  <TableCell className="px-4 font-medium">{p.aluno}</TableCell>

                  <TableCell className="px-4 text-center">
                    <Badge variant="secondary">
                      {metodoLabels[p.metodo] ?? p.metodo}
                    </Badge>
                  </TableCell>

                  <TableCell className="px-4 font-medium text-center">
                    {formatCurrency(p.valor_total)}
                  </TableCell>

                  <TableCell className="px-4 text-muted-foreground text-center">
                    {p.data_pagamento}
                  </TableCell>

                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger
                        asChild
                        onClick={(e) => e.stopPropagation()}
                      >
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Abrir menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(show(p.id).url);
                          }}
                        >
                          Ver detalhes
                        </DropdownMenuItem>

                       
                        {/* <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(p.id);
                          }}
                        >
                          Anular
                        </DropdownMenuItem> */}
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
