import { Link } from '@inertiajs/react';
import {
  LayersIcon,
  MoreHorizontalIcon,
  Pencil,
  ShieldCheck,
  Trash2,
} from 'lucide-react';
import {
  create,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/RoleController';
import { EmptyState } from '@/components/empty-state';
import TablePagination from '@/components/table-pagination';
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
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

export function RoleTable({ roles, pagination, onPageChange, deleteFn }) {
  const isEmpty = !roles?.data?.length;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Funções</CardTitle>
        <CardDescription>Funções e permissões disponíveis</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={create().url}>Adicionar função</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={ShieldCheck}
            title="Nenhuma função cadastrada"
            description="Clique no botão abaixo para adicionar uma nova função."
            action={{
              label: 'Adicionar função',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4 text-center">Usuários</TableHead>
                <TableHead className="px-4">Permissões</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {roles.data.map((role) => {
                const permissionLabels = Array.isArray(role.permissions)
                  ? role.permissions.map(
                      (permission) => permission.label ?? permission.name,
                    )
                  : [];
                const visiblePermissions = permissionLabels.slice(0, 3);
                const hiddenPermissionsCount = Math.max(
                  permissionLabels.length - visiblePermissions.length,
                  0,
                );

                return (
                  <TableRow key={role.id}>
                    <TableCell className="px-4 font-medium">
                      {role.name}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      {role.users_count}
                    </TableCell>
                    <TableCell className="max-w-xl px-4">
                      <div
                        className="flex flex-wrap items-center gap-1 text-xs text-muted-foreground"
                        title={permissionLabels.join(', ')}
                      >
                        {visiblePermissions.length > 0 ? (
                          <>
                            <span className="truncate">
                              {visiblePermissions.join(', ')}
                            </span>
                            {hiddenPermissionsCount > 0 && (
                              <Badge size="sm" className="text-[10px]">
                                +{hiddenPermissionsCount}
                              </Badge>
                            )}
                          </>
                        ) : (
                          <span>Nenhuma</span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="outline" size="icon-sm">
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem asChild>
                            <Link href={edit(role.id).url}>Editar</Link>
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            variant="destructive"
                            onClick={() => deleteFn(role)}
                          >
                            Remover
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
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
