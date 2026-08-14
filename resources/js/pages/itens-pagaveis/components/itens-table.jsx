import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon, TriangleAlert } from 'lucide-react';
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
  edit,
} from '@/actions/App/Http/Controllers/ItemPagavelController';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return Number.isNaN(amount) ? '—' : `${amount.toLocaleString('pt')} AOA`;
};

const frequenciaLabels = {
  mensal: 'Mensal',
  anual: 'Anual',
  unico: 'Único',
};

export default function ItensTable({
  itens = [],
  can,
  pagination = {},
  onPageChange,
}) {
  const hasAnyAction = itens.some((i) => i.can?.update || i.can?.delete);
  const isEmpty = !itens || itens.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Emolumentos Escolares</CardTitle>
        <CardDescription>
          Emolumentos utilizados para cobrar encargos escolares
        </CardDescription>
        <CardAction>
          {can?.create && (
            <Button asChild>
              <Link href={create().url}>Novo emolumento</Link>
            </Button>
          )}
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum emolumento encontrado"
            description="Adicione emolumentos para começar a cobrar"
            action={
              can?.create
                ? {
                    label: 'Adicionar emolumento',
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
                <TableHead className="px-4 text-center">Aplicada a</TableHead>
                <TableHead className="px-4 text-center">Frequência</TableHead>
                <TableHead className="px-4 text-center">Valor</TableHead>
                <TableHead className="px-4 text-center">Multa</TableHead>
                <TableHead className="px-4 text-center">
                  Estado {/** Bloqueia estudantes? */}
                </TableHead>
                {hasAnyAction && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>

            <TableBody>
              {itens.map((item) => {
                const temMulta = item.multa_dias_tolerancia && item.multa_valor;

                return (
                  <TableRow key={item.id}>
                    <TableCell className="px-4 font-medium">
                      {item.nome}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      {item.curso_classe ?? 'Toda a instituição'}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      {frequenciaLabels[item.frequencia] ?? item.frequencia}
                    </TableCell>

                    <TableCell className="px-4 text-center font-medium">
                      {formatCurrency(item.valor)}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      {temMulta ? (
                        <span className="inline-flex items-center gap-1 text-xs  ">
                          {formatCurrency(item.multa_valor)} após dia {item.multa_dias_tolerancia}
                        </span>
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      <Badge variant={item.ativo ? 'default' : 'secondary'}>
                        {item.ativo ? 'Activo' : 'Inactivo'}
                      </Badge>
                    </TableCell>

                    {hasAnyAction && (
                      <TableCell className="px-4 text-right">
                        {(item.can?.update || item.can?.delete) && (
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
                              {item.can?.update && (
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    router.visit(edit(item.id).url);
                                  }}
                                >
                                  Editar
                                </DropdownMenuItem>
                              )}
                            </DropdownMenuContent>
                          </DropdownMenu>
                        )}
                      </TableCell>
                    )}
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}