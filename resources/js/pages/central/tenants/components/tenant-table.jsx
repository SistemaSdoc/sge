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

export function TenantTable({
  tenants,
  can = {},
  deleteFn,
  pagination = {},
  onPageChange,
  handleToggleStatus,
}) {
  const isEmpty = tenants?.length === 0;

  const hasActionColumn = tenants.some(
    (tenants) => tenants.can?.edit || tenants.can?.delete || true,
  );

  return (
    <div className="w-full p-6 mx-auto max-w-7xl">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Clientes</CardTitle>
          <CardDescription>Lista de clientes cadastrados</CardDescription>
          <CardAction>
            {/*{can.create && (
              <Button asChild>
                <Link href={create().url}>Adicionar</Link>
              </Button>
            )}*/}

            <Button asChild>
              <Link href={create().url}>Adicionar</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title="Nenhuma cliente cadastrado"
              description="Clique no botão abaixo para cadastrar um novo cliente"
              action={
                can.create
                  ? {
                      label: 'Adicionar Cliente',
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
                  <TableHead className="px-4 text-center">Sigla</TableHead>
                  <TableHead className="px-4 text-center">Domínio</TableHead>
                  <TableHead className="px-4 text-center">Status</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {tenants.map((tenant) => (
                  <TableRow
                    key={tenant.id}
                    onClick={() => {
                      router.visit(show(tenant.id).url);
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {tenant.instituicao?.nome ?? tenant.id}
                    </TableCell>

                    <TableCell className="px-4 text-center ">
                      {tenant.instituicao?.sigla ?? '—'}
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
