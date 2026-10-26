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
  index,
  show,
  create,
  edit,
} from '@/actions/App/Http/Controllers/Central/TenantController';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import TablePagination from '@/components/table-pagination';
import { StatusBadge } from './status-badge';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function TenantTable({
  tenants,
  filters,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
  handleToggleStatus,
  recreateDatabaseFn,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['tenants', 'filters'],
    },
  );
  const isEmpty = tenants?.length === 0;

  const filtrarTipo = (valor) => {
  router.get(
    index().url,
    {
      search: filters?.search || undefined,
      tipo: valor === 'todos' ? undefined : valor,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  );
    };

  const hasActionColumn = tenants.some(
    (tenants) => tenants.can?.edit || tenants.can?.delete || true,
  );

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Instituições</CardTitle>
          <CardDescription>Lista de instituições cadastradas</CardDescription>
          <CardAction>
            <Button asChild>
              <Link href={create().url}>Adicionar Instituição</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
        <TableSearch value={search} onChange={onChange} onSubmit={submit}>
        <Select value={filters?.tipo || 'todos'} onValueChange={filtrarTipo}>
          <SelectTrigger className="w-48">
            <SelectValue placeholder="Tipo" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Todos</SelectItem>
            <SelectItem value="colegio">Colégios</SelectItem>
            <SelectItem value="instituto">Institutos</SelectItem>
          </SelectContent>
        </Select>
      </TableSearch>
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title={
                applied
                  ? 'Nenhuma instituição encontrada'
                  : 'Nenhuma instituição cadastrada'
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : 'Clique no botão abaixo para cadastrar uma nova instituição'
              }
              action={
                can.create
                  ? {
                      label: 'Adicionar Instituição',
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
                  <TableHead className="px-4 text-center">Domínio</TableHead>
                  <TableHead className="px-4 text-center">Status</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {tenants.map((tenant) => (
                  <TableRow
                    key={tenant.id}
                    className="hover:cursor-pointer"
                    onClick={() => {
                      router.visit(show(tenant.id).url);
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {tenant.instituicao?.nome ?? tenant.id}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      {tenant.domains?.[0]?.domain ?? '—'}
                    </TableCell>

                    <TableCell className="px-4 text-center">
                      <StatusBadge status={tenant.status} variant="badge" />
                    </TableCell>

                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="outline"
                            size="icon-sm"
                            className="hover:cursor-pointer"
                          >
                            <MoreHorizontalIcon />
                            <span className="sr-only">Open menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              router.visit(edit(tenant.id).url);
                            }}
                            className="hover:cursor-pointer"
                          >
                            Editar
                          </DropdownMenuItem>

                          <DropdownMenuSeparator />

                          {/* {tenant.status !== 'pending' &&
                            tenant.status !== 'provisioning' && (
                              <>
                                <DropdownMenuItem
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    recreateDatabaseFn(tenant);
                                  }}
                                >
                                  Recriar base de dados
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />
                              </>
                            )} */}

                          <DropdownMenuItem
                            onClick={(e) => {
                              e.stopPropagation();
                              handleToggleStatus(tenant, e);
                            }}
                            className="hover:cursor-pointer"
                          >
                            Alterar status
                          </DropdownMenuItem>

                          <DropdownMenuSeparator />

                          <DropdownMenuItem
                            variant="destructive"
                            onClick={(e) => {
                              e.stopPropagation();
                              deleteFn(tenant.id);
                            }}
                            className="hover:cursor-pointer"
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
