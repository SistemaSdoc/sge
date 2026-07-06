import { Link, router } from '@inertiajs/react';
import { LayersIcon, Settings2, ChevronRight } from 'lucide-react';
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
} from '@/actions/App/Http/Controllers/ClasseController';
import TablePagination from '@/components/table-pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export function UsuariosTable({
  usuarios,
  pagination = {},
  onPageChange,
  editarAcessoFn,
}) {
  const getInitials = useInitials();
  const isEmpty = usuarios?.data?.length === 0;

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Usuários</CardTitle>
          <CardDescription>Lista de usuários cadastrados</CardDescription>
          <CardAction>
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
              title="Nenhum usuário cadastrado"
              description="Comece adicionando o primeiro usuário à tabela"
              action={{
                label: 'Adicionar Usuário',
                href: create().url,
                variant: 'outline',
              }}
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Usuário</TableHead>
                  <TableHead className="px-4">Roles</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {usuarios?.data?.map((usuario) => (
                  <TableRow
                    key={usuario.id}
                    className="hover:cursor-pointer"
                    onClick={() => router.visit(show(usuario.id).url)}
                  >
                    <TableCell className="px-4 font-medium">
                      <div className="flex items-center gap-3">
                        <Avatar>
                          <AvatarImage
                            src={usuario.avatar}
                            alt={usuario.nome}
                            className="grayscale"
                          />
                          <AvatarFallback>
                            {getInitials(usuario.nome)}
                          </AvatarFallback>
                        </Avatar>

                        <div className="flex flex-col">
                          <span className="text-xs">{usuario.nome}</span>
                          <span className="text-[10px] text-muted-foreground">
                            {usuario.email}
                          </span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell className="px-4">
                      {usuario.roles.join(', ')}
                    </TableCell>

                    <TableCell
                      className="px-4 text-right"
                      onClick={(e) => {
                        e.stopPropagation();
                        editarAcessoFn(usuario);
                      }}
                    >
                      <div className="flex items-center justify-end gap-2">
                        <Settings2 size={14} />

                        <span>Gerir Acessos</span>

                        <ChevronRight size={14} />
                      </div>
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
