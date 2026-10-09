import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
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
import {
  show,
  create,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/RegraAvaliacaoController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function RegraTable({
  regras,
  filters,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['regrasAvaliacao', 'filters'],
    },
  );
  const isEmpty = regras?.data.length === 0;

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Regras de Avaliação</CardTitle>
          <CardDescription>Lista de regras cadastradas</CardDescription>
          <CardAction>
            <Button asChild>
              <Link href={create().url}>Adicionar</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          <TableSearch value={search} onChange={onChange} onSubmit={submit} />
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title={
                applied
                  ? 'Nenhuma regra encontrada'
                  : 'Nenhuma regra cadastrada'
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : 'Comece adicionando a primeira regra à tabela'
              }
              action={{
                label: 'Adicionar Regra',
                href: create().url,
                variant: 'outline',
              }}
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead className="px-4">Nível de Ensino</TableHead>
                  <TableHead className="px-4">Aplicada a</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {regras?.data.map((regra) => (
                  <TableRow
                    key={regra.id}
                    className={'hover:cursor-pointer'}
                    onClick={() => {
                      router.visit(show(regra.id).url);
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {regra.nome}
                    </TableCell>

                    <TableCell className="px-4">
                      {regra.nivelEnsino || ''}
                    </TableCell>

                    <TableCell className="px-4">{regra.aplicacao}</TableCell>

                    <TableCell className="px-4 text-right">
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
                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(edit(regra.id).url);
                            }}
                          >
                            Editar
                          </DropdownMenuItem>

                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            variant="destructive"
                            onClick={(e) => {
                              e.stopPropagation();
                              deleteFn(regra.id);
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
    </div>
  );
}
