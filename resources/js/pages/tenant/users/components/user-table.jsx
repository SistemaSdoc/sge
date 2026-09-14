import { Link } from '@inertiajs/react';
import { MoreHorizontalIcon, Users } from 'lucide-react';
import {
  edit,
  create,
} from '@/actions/App/Http/Controllers/Tenant/UserController';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export function UserTable({ users, pagination, onPageChange, deleteFn }) {
  const isEmpty = !users?.data?.length;
  const getInitials = useInitials();

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Usuários</CardTitle>
        <CardDescription>Lista de usuários cadastrados</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={create().url}>Adicionar usuário</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users}
            title="Nenhum usuário cadastrado"
            description="Clique no botão abaixo para adicionar um usuário."
            action={{
              label: 'Adicionar usuário',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Funções</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.data.map((user) => {
                const visibleRoles = user.roles.slice(0, 3);
                const hiddenRolesCount = Math.max(
                  user.roles.length - visibleRoles.length,
                  0,
                );
                const canEditUser = Boolean(user.can?.update);
                const canDeleteUser = Boolean(user.can?.delete);
                const canManagePermissions = Boolean(
                  user.can?.manage_permissions,
                );

                return (
                  <TableRow key={user.id}>
                    <TableCell className="px-4 font-medium">
                      <div className="flex items-center gap-3">
                        <Avatar>
                          <AvatarImage src={user.avatar} alt={user.nome} />
                          <AvatarFallback>
                            {getInitials(user.nome)}
                          </AvatarFallback>
                        </Avatar>

                        <div className="flex flex-col">
                          <span className="text-xs">{user.nome}</span>
                          <span className="text-[10px] text-muted-foreground">
                            {user.email}
                          </span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell className="px-4">
                      <div
                        className="flex flex-wrap items-center gap-1"
                        title={user.roles.join(', ')}
                      >
                        {visibleRoles.length ? (
                          <>
                            <span>{visibleRoles.join(', ')}</span>
                            {hiddenRolesCount > 0 && (
                              <Badge className="text-[10px]">
                                +{hiddenRolesCount}
                              </Badge>
                            )}
                          </>
                        ) : (
                          <span className="text-muted-foreground">
                            Sem role
                          </span>
                        )}
                      </div>
                    </TableCell>

                    <TableCell className="px-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        {canManagePermissions && (
                          <Button variant="outline" size="xs">
                            <Link
                              href={`/dashboard/users/${user.id}/permissions`}
                            >
                              Gerir permissões
                            </Link>
                          </Button>
                        )}

                        {canEditUser && (
                          <Button variant="outline" size="xs">
                            <Link href={edit(user.id).url}>Editar</Link>
                          </Button>
                        )}

                        {canDeleteUser && (
                          <Button
                            variant="destructive"
                            size="xs"
                            onClick={() => deleteFn(user)}
                          >
                            Remover
                          </Button>
                        )}
                      </div>
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
