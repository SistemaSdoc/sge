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
} from '@/actions/App/Http/Controllers/Central/TenantController';
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

  const hasActionColumn = tenants.some(
    (tenants) => tenants.can?.edit || tenants.can?.delete || true,
  );

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
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
          <TableSearch value={search} onChange={onChange} onSubmit={submit} />
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
                              router.visit(edit(tenant.id).url);
                            }}
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
