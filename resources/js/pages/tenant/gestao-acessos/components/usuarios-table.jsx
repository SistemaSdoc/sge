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
} from '@/actions/App/Http/Controllers/Tenant/ClasseController';
import TablePagination from '@/components/table-pagination';
import { ProfileAvatarPreview } from '@/components/profile-avatar-preview';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function UsuariosTable({
  usuarios,
  filters,
  pagination = {},
  onPageChange,
  editarAcessoFn,
}) {
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['users', 'filters'],
    },
  );
  const isEmpty = usuarios?.data?.length === 0;

  const renderRolesBadge = (roles = []) => {
    const safeRoles = Array.isArray(roles) ? roles : [];
    const visibleRoles = safeRoles.slice(0, 3);
    const hiddenCount = Math.max(safeRoles.length - visibleRoles.length, 0);

    return (
      <div
        className="flex flex-wrap items-center gap-1"
        title={safeRoles.join(', ')}
      >
        {visibleRoles.map((role, index) => (
          <span
            key={`${role}-${index}`}
            className="inline-flex rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[10px] font-medium text-orange-700"
          >
            {role}
          </span>
        ))}

        {hiddenCount > 0 && (
          <span className="inline-flex rounded-full border border-muted bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
            +{hiddenCount}
          </span>
        )}
      </div>
    );
  };

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
          <TableSearch value={search} onChange={onChange} onSubmit={submit} />
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title={
                applied
                  ? 'Nenhum usuário encontrado'
                  : 'Nenhum usuário cadastrado'
              }
              description={
                applied
                  ? 'Tenta ajustar a pesquisa.'
                  : 'Comece adicionando o primeiro usuário à tabela'
              }
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
                        <ProfileAvatarPreview
                          src={usuario.avatar}
                          name={usuario.nome}
                          className="grayscale"
                        />

                        <div className="flex flex-col">
                          <span className="text-xs">{usuario.nome}</span>
                          <span className="text-[10px] text-muted-foreground">
                            {usuario.email}
                          </span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell className="px-4">
                      {renderRolesBadge(usuario.roles)}
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
