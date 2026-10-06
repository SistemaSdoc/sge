import { Link, router } from '@inertiajs/react';
import { MoreHorizontalIcon, LayersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
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
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/Tenant/ProfessorController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function ProfessorTable({
  professores,
  filters,
  deleteFn,
  pagination = {},
  onPageChange,
}) {
  const { search, onChange, submit, applied } = useTableSearch(filters?.search, {
    only: ['professores', 'filters'],
  });
  const isEmpty = !professores || professores.length === 0;

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Lista de professores cadastrados</CardDescription>
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
            title={applied ? 'Nenhum professor encontrado' : 'Nenhum professor cadastrado'}
            description={applied ? 'Tenta ajustar a pesquisa.' : undefined}
            action={{
              label: 'Adicionar Professor',
              href: create().url,
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4">Telefone</TableHead>
                <TableHead className="px-4">Especialidade</TableHead>
                <TableHead className="px-4">Nível Académico</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {professores.data.map((professor) => (
                <TableRow
                  key={professor.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(show(professor.id).url)}
                >
                  <TableCell className="px-4 font-medium">
                    {professor.user.nome}
                  </TableCell>

                  <TableCell className="px-4 font-medium">
                    {professor.user.telefone}
                  </TableCell>

                  <TableCell className="px-4">
                    {professor.especialidade || '-'}
                  </TableCell>

                  <TableCell className="px-4">
                    {professor.nivel_academico || '-'}
                  </TableCell>
                  <TableCell className="px-4 text-right">
                    <div className="flex justify-end gap-2">
                      <Button
                        variant="outline"
                        size="xs"
                        className="text-[10px]"
                        onClick={(e) => {
                          e.stopPropagation();
                          router.visit(edit(professor.id).url);
                        }}
                      >
                        Editar
                      </Button>

                      <Button
                        variant="destructive"
                        size="xs"
                        className="text-[10px]"
                        onClick={(e) => {
                          e.stopPropagation();
                          deleteFn(professor.id);
                        }}
                      >
                        Remover
                      </Button>
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
  );
}
