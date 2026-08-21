import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, UsersIcon } from 'lucide-react';
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
import {
  create,
  edit,
} from '@/actions/App/Http/Controllers/Central/UserController';

export default function UserTable({
  users,
  can,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const hasAnyAction = users.some((u) => u.can?.update || u.can?.delete);
  const isEmpty = !users || users.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Utilizadores</CardTitle>
        <CardDescription>Lista de utilizadores registados</CardDescription>
        {can?.create && (
          <CardAction>
            <Button asChild>
              <Link href={create().url}>Adicionar</Link>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum utilizador registado"
            description="Comece adicionando o primeiro utilizador"
            action={
              can?.create
                ? {
                    label: 'Adicionar utilizador',
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
                <TableHead className="px-4">Email</TableHead>
                <TableHead className="px-4">Telefone</TableHead>
                <TableHead className="px-4">Perfis</TableHead>
                {hasAnyAction && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>

            <TableBody>
              {users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell className="px-4 font-medium">{user.nome}</TableCell>
                  <TableCell className="px-4">{user.email}</TableCell>
                  <TableCell className="px-4">{user.telefone ?? '—'}</TableCell>
                  <TableCell className="px-4">
                    <div className="flex flex-wrap gap-1">
                      {user.roles?.length ? (
                        user.roles.map((role) => (
                          <span
                            key={role.id}
                            className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                          >
                            {role.name}
                          </span>
                        ))
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                    </div>
                  </TableCell>

                  {hasAnyAction && (
                    <TableCell className="px-4 text-right">
                      {(user.can?.update || user.can?.delete) && (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-8">
                              <MoreHorizontalIcon />
                              <span className="sr-only">Abrir menu</span>
                            </Button>
                          </DropdownMenuTrigger>

                          <DropdownMenuContent align="end">
                            {user.can?.update && (
                              <DropdownMenuItem
                                onClick={() => router.visit(edit(user.id).url)}
                              >
                                Editar
                              </DropdownMenuItem>
                            )}
                            {user.can?.update && user.can?.delete && (
                              <DropdownMenuSeparator />
                            )}
                            {user.can?.delete && (
                              <DropdownMenuItem
                                variant="destructive"
                                onClick={() => deleteFn(user.id)}
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