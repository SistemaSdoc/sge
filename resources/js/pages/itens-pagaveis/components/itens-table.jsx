import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
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
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const hasAnyAction = itens.some((i) => i.can?.update || i.can?.delete);
  const isEmpty = !itens || itens.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Itens pagáveis</CardTitle>
        <CardDescription>
          Itens utilizados para cobrar encargos escolares
        </CardDescription>
        <CardAction>
          {can?.create && (
            <Button asChild>
              <Link href={create().url}>Novo item</Link>
            </Button>
          )}
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum item encontrado"
            description="Adicione itens pagáveis para começar a cobrar"
            action={
              can?.create
                ? {
                    label: 'Adicionar item',
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
                <TableHead className="px-4">Curso / Classe</TableHead>
                <TableHead className="px-4">Frequência</TableHead>
                <TableHead className="px-4">Valor</TableHead>
                <TableHead className="px-4">Estado</TableHead>
                {hasAnyAction && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {itens.map((item) => (
                <TableRow key={item.id}>
                  <TableCell className="px-4 font-medium">
                    {item.nome}
                  </TableCell>
                  <TableCell className="px-4 text-muted-foreground">
                    {item.curso_classe ?? 'Toda a instituição'}
                  </TableCell>
                  <TableCell className="px-4 text-muted-foreground">
                    {frequenciaLabels[item.frequencia] ?? item.frequencia}
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {formatCurrency(item.valor)}
                  </TableCell>
                  <TableCell className="px-4">
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

                            {item.can?.update && item.can?.delete && (
                              <DropdownMenuSeparator />
                            )}

                            {item.can?.delete && (
                              <DropdownMenuItem
                                variant="destructive"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  deleteFn(item.id);
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
